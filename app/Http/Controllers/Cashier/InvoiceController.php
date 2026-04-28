<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Inventory;
use App\Models\Technician;
use App\Services\ClientVehicleResolver;
use App\Support\CashierListLimits;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * @return array<int, string>
     */
    protected function allowedPaymentTypes(): array
    {
        return ['cash', 'debit', 'credit', 'non_cash', 'gcash', 'split'];
    }

    protected function validatePaymentSplit(Request $request): void
    {
        $gt = round((float) $request->input('grand_total', 0), 2);
        if (($request->input('status') ?? '') !== 'paid') {
            return;
        }
        $pc = round((float) $request->input('payment_cash_amount', 0), 2);
        $pn = round((float) $request->input('payment_non_cash_amount', 0), 2);
        $cashChangeRaw = $request->input('cash_change_amount');
        $ch = ($cashChangeRaw !== null && $cashChangeRaw !== '') ? round((float) $cashChangeRaw, 2) : 0.0;
        /** Both rails used — same inference as cashier UI “split”: allow overpayment as Grand Total + change. */
        $isSplitTender = $pc > 0.005 && $pn > 0.005;
        if ($isSplitTender) {
            if (abs($pc + $pn - $gt - $ch) > 0.05) {
                throw ValidationException::withMessages([
                    'payment_cash_amount' => 'For split payments when Paid: cash plus cashless must equal Grand Total plus Change.',
                ]);
            }

            return;
        }
        if (abs($pc + $pn - $gt) > 0.05) {
            throw ValidationException::withMessages([
                'payment_cash_amount' => 'Cash amount plus cashless amount must equal Grand Total when status is Paid.',
            ]);
        }
    }

    public function index(Request $request)
    {
        $clients = collect();
        $vehicles = collect();
        $partsPrefill = [];
        $technicians = Technician::all();
        $search = $request->input('search');

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'invoicing')
            ->latest('created_at')
            ->limit(CashierListLimits::SIDEBAR_INVOICE_HISTORY)
            ->get();

        $recentAll = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'invoicing')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', fn($q) => $q->where('name', 'like', "%$search%"))
                        ->orWhereHas('vehicle', fn($q) => $q->where('plate_number', 'like', "%$search%"))
                        ->orWhere('customer_name', 'like', "%$search%")
                        ->orWhere('vehicle_name', 'like', "%$search%")
                        ->orWhere('invoice_no', 'like', "%$search%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends(['search' => $search]);


        return view('cashier.invoice', compact('clients', 'vehicles', 'partsPrefill', 'technicians', 'history', 'recentAll', 'search'));
    }

    public function create()
    {
        $clients = collect();
        $vehicles = collect();
        $partsPrefill = [];
        $technicians = Technician::all();
        $history = collect([]);

        return view('cashier.invoice', compact('clients', 'vehicles', 'partsPrefill', 'technicians', 'history'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'customer_name' => 'nullable|string',
            'vehicle_name' => 'nullable|string',
            'plate' => 'nullable|string',
            'model' => 'nullable|string',
            'year' => 'nullable|string',
            'color' => 'nullable|string',
            'odometer' => 'nullable|string',
            'subtotal' => 'required|numeric',
            'total_discount' => 'nullable|numeric|min:0',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_type' => ['required', Rule::in($this->allowedPaymentTypes())],
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0',
            'cash_tender_amount' => 'nullable|numeric|min:0',
            'cash_change_amount' => 'nullable|numeric',
            'cashless_tender_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:unpaid,paid,cancelled,voided',
            'service_status' => 'required|in:pending,in_progress,done',
            'invoice_no' => 'required|string|unique:invoices,invoice_no',
            'number' => 'nullable|string',
            'address' => 'nullable|string',
            'created_date' => 'nullable|date',
        ]);

        $this->validatePaymentSplit($request);

        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);
        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $date = $request->input('created_date') ?? now();

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $clientId, $vehicleId, $manualCustomer, $date) {
            $invoice = new Invoice();
            $invoice->forceFill([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'vehicle_name' => $vehicleId ? null : $request->vehicle_name,
                'source_type' => 'invoicing',
                'service_status' => ($request->status ?? 'unpaid') === 'paid'
                    ? 'done'
                    : ($request->service_status ?? 'pending'),
                'status' => $request->status ?? 'unpaid',
                'subtotal' => $request->subtotal,
                'total_discount' => $request->input('total_discount', 0),
                'vat_amount' => $request->vat_amount,
                'grand_total' => $request->grand_total,
                'payment_type' => $request->payment_type,
                'payment_cash_amount' => $request->filled('payment_cash_amount') ? $request->payment_cash_amount : null,
                'payment_non_cash_amount' => $request->filled('payment_non_cash_amount') ? $request->payment_non_cash_amount : null,
                'cash_tender_amount' => $request->filled('cash_tender_amount') ? $request->cash_tender_amount : null,
                'cash_change_amount' => $request->filled('cash_change_amount') ? $request->cash_change_amount : null,
                'cashless_tender_amount' => $request->filled('cashless_tender_amount') ? $request->cashless_tender_amount : null,
                'invoice_no' => $request->invoice_no,
                'number' => $request->number,
                'address' => $request->address,
                'created_at' => $date,
            ])->save();

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $original = $item['original_price'] ?? $item['price'] ?? $item['manual_selling_price'] ?? 0;

                    $discount = $item['discount_value'] ?? 0;
                    $effective = $original - $discount;
                    $qty = $item['quantity'] ?? 0;
                    $lineTotal = $qty * $effective;

                    $invoice->items()->create([
                        'part_id' => $item['part_id'] ?? null,
                        'manual_part_name' => $item['manual_part_name'] ?? null,
                        'manual_serial_number' => $item['manual_serial_number'] ?? null,
                        'manual_acquisition_price' => $item['manual_acquisition_price'] ?? null,
                        'manual_selling_price' => $item['manual_selling_price'] ?? null,
                        'quantity' => $qty,
                        'original_price' => $original,
                        'discount_value' => $discount,
                        'discounted_price' => $lineTotal,
                        'line_total' => $lineTotal,
                    ]);
                }
            }
            if ($request->has('jobs')) {
                foreach ($request->jobs as $job) {
                    $invoice->jobs()->create([
                        'job_description' => $job['job_description'] ?? '',
                        'technician_id' => $job['technician_id'] ?? null,
                        'total' => $job['total'] ?? 0,
                    ]);
                }
            }

            if ($invoice->status === 'paid') {
                $invoice->load('items');
                foreach ($invoice->items as $item) {
                    $inventory = Inventory::find($item->part_id);
                    if ($inventory) {
                        $inventory->deductQuantity($item->quantity);
                    }
                }
            }
        });

        return redirect()->route('cashier.invoice.index')->with('success', 'Invoice created!');
    }


    public function edit($id)
    {
        $invoice = Invoice::with(['items', 'jobs', 'client', 'vehicle'])->findOrFail($id);
        $clients = $invoice->client_id
            ? Client::where('id', $invoice->client_id)->get(['id', 'name'])
            : collect();
        $vehicles = $invoice->vehicle_id
            ? Vehicle::where('id', $invoice->vehicle_id)->get(['id', 'plate_number', 'model', 'client_id'])
            : collect();
        $partsPrefill = Inventory::partPickerPrefillByIds($invoice->items->pluck('part_id'));
        $technicians = Technician::all();

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'invoicing')
            ->latest('created_at')
            ->limit(CashierListLimits::SIDEBAR_INVOICE_HISTORY)
            ->get();

        $recentAll = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'invoicing')
            ->orderBy('created_at', 'desc')
            ->paginate(10); // ✅ Now returns a paginator object compatible with ->links()


        return view('cashier.invoice', compact('invoice', 'clients', 'vehicles', 'partsPrefill', 'technicians', 'history', 'recentAll'));
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $prevStatus = $invoice->status;

        // #region agent log
        file_put_contents(base_path('.cursor/debug-c4fe64.log'), json_encode([
            'sessionId' => 'c4fe64',
            'location' => 'InvoiceController.php:update',
            'message' => 'update entry',
            'data' => [
                'hypothesisId' => 'H_server',
                'id' => (int) $id,
                'has_status' => $request->has('status'),
                'status' => $request->input('status'),
                'has_items' => $request->has('items'),
                'items_is_array' => is_array($request->input('items')),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        // If this is a quick "mark as paid" or service_status update (not full edit)
        if ($request->has('status') && $request->method() == 'PUT' && !$request->has('items')) {
            DB::transaction(function () use ($invoice, $request, $prevStatus) {
                $serviceStatus = ($request->status === 'paid')
                    ? 'done'
                    : ($request->service_status ?? $invoice->service_status);
                $invoice->update([
                    'status' => $request->status,
                    'service_status' => $serviceStatus,
                ]);

                if ($prevStatus !== 'paid' && $request->status === 'paid') {
                    $invoice->load('items');
                    foreach ($invoice->items as $item) {
                        $inventory = Inventory::find($item->part_id);
                        if ($inventory) {
                            $inventory->deductQuantity($item->quantity);
                        }
                    }
                }
            });

            return redirect()->route('cashier.invoice.index')->with('success', 'Status updated!');
        }

        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'customer_name' => 'nullable|string',
            'vehicle_name' => 'nullable|string',
            'plate' => 'nullable|string',
            'model' => 'nullable|string',
            'year' => 'nullable|string',
            'color' => 'nullable|string',
            'odometer' => 'nullable|string',
            'subtotal' => 'required|numeric',
            'total_discount' => 'nullable|numeric|min:0',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_type' => ['required', Rule::in($this->allowedPaymentTypes())],
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0',
            'cash_tender_amount' => 'nullable|numeric|min:0',
            'cash_change_amount' => 'nullable|numeric',
            'cashless_tender_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:unpaid,paid,cancelled,voided',
            'service_status' => 'required|in:pending,in_progress,done',
            'invoice_no' => 'required|string|unique:invoices,invoice_no,' . $invoice->id,

            'created_date' => 'nullable|date',
        ]);

        $this->validatePaymentSplit($request);

        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);
        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $date = $request->input('created_date') ?? now();

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $invoice, $clientId, $vehicleId, $manualCustomer, $date, $prevStatus) {
            $nextStatus = $request->status ?? 'unpaid';
            $nextService = ($nextStatus === 'paid')
                ? 'done'
                : ($request->service_status ?? 'pending');
            $invoice->update([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'vehicle_name' => $vehicleId ? null : $request->vehicle_name,
                'source_type' => 'invoicing',
                'service_status' => $nextService,
                'status' => $nextStatus,
                'subtotal' => $request->subtotal,
                'total_discount' => $request->input('total_discount', 0),
                'created_at' => $date,

                'vat_amount' => $request->vat_amount,
                'grand_total' => $request->grand_total,
                'payment_type' => $request->payment_type,
                'payment_cash_amount' => $request->filled('payment_cash_amount') ? $request->payment_cash_amount : null,
                'payment_non_cash_amount' => $request->filled('payment_non_cash_amount') ? $request->payment_non_cash_amount : null,
                'cash_tender_amount' => $request->filled('cash_tender_amount') ? $request->cash_tender_amount : null,
                'cash_change_amount' => $request->filled('cash_change_amount') ? $request->cash_change_amount : null,
                'cashless_tender_amount' => $request->filled('cashless_tender_amount') ? $request->cashless_tender_amount : null,
                'invoice_no' => $request->invoice_no,
            ]);

            $invoice->items()->delete();
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $original = $item['original_price'] ?? $item['price'] ?? $item['manual_selling_price'] ?? 0;

                    $discount = $item['discount_value'] ?? 0;
                    $effective = $original - $discount;
                    $qty = $item['quantity'] ?? 0;
                    $lineTotal = $qty * $effective;

                    $invoice->items()->create([
                        'part_id' => $item['part_id'] ?? null,
                        'manual_part_name' => $item['manual_part_name'] ?? null,
                        'manual_serial_number' => $item['manual_serial_number'] ?? null,
                        'manual_acquisition_price' => $item['manual_acquisition_price'] ?? null,
                        'manual_selling_price' => $item['manual_selling_price'] ?? null,
                        'quantity' => $qty,
                        'original_price' => $original,
                        'discount_value' => $discount,
                        'discounted_price' => $lineTotal,
                        'line_total' => $lineTotal,
                    ]);
                }
            }

            $invoice->jobs()->delete();
            if ($request->has('jobs')) {
                foreach ($request->jobs as $job) {
                    $techId = !empty($job['technician_id']) ? $job['technician_id'] : null;
                    $invoice->jobs()->create([
                        'job_description' => $job['job_description'] ?? '',
                        'technician_id' => $techId,
                        'total' => $job['total'] ?? 0,
                    ]);
                }
            }

            if ($prevStatus !== 'paid' && $request->status === 'paid') {
                $invoice->load('items');
                foreach ($invoice->items as $item) {
                    $inventory = Inventory::find($item->part_id);
                    if ($inventory) {
                        $inventory->deductQuantity($item->quantity);
                    }
                }
            }
        });

        return redirect()->route('cashier.invoice.index')->with('success', 'Invoice updated!');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->jobs()->delete();
            $invoice->delete();
        });

        return redirect()->route('cashier.invoice.index')->with('success', 'Invoice deleted!');
    }

    public function view($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        return view('cashier.invoice-view', compact('invoice'));
    }

    public function show($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        return view('cashier.invoice-view', compact('invoice'));
    }

    public function ajaxClients(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $query = Client::query()->select(['id', 'name', 'phone', 'address', 'email']);

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('vehicles', fn ($vq) => $vq->where('plate_number', 'like', $like));
            });
        }

        $rows = $query
            ->orderForSelect2Dropdown()
            ->limit(20)
            ->get();

        $ids = $rows->pluck('id')->all();
        $invNames = Client::latestInvoiceCustomerNameByClientId($ids);
        $plates = Client::primaryVehiclePlateByClientId($ids);

        return $rows->map(fn (Client $c) => [
            'id' => $c->id,
            'name' => $c->select2Label($invNames->get($c->id), $plates->get($c->id)),
            'number' => $c->phone,
            'address' => $c->address,
            'email' => $c->email,
        ]);
    }

    public function ajaxVehicles(Request $request)
    {
        $search = $request->get('q', '');
        $clientId = $request->get('client_id');

        return Vehicle::where('plate_number', 'like', "%$search%")
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->select('id', 'plate_number', 'model', 'year', 'color', 'odometer')
            ->limit(20)
            ->get();

    }

    public function ajaxParts(Request $request)
    {
        return Inventory::partPickerAjaxResponse($request);
    }

    public function liveSearch(Request $request)
    {
        $search = $request->input('search');

        $results = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'invoicing')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', fn($q) => $q->where('name', 'like', "%$search%"))
                        ->orWhereHas('vehicle', fn($q) => $q->where('plate_number', 'like', "%$search%"))
                        ->orWhere('customer_name', 'like', "%$search%")
                        ->orWhere('vehicle_name', 'like', "%$search%")
                        ->orWhere('invoice_no', 'like', "%$search%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('cashier.partials.invoice-results', ['results' => $results])->render();
    }




}
