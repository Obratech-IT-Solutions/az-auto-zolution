<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Inventory; // your "parts"
use App\Models\Technician;
use App\Services\ClientVehicleResolver;
use App\Support\CashierListLimits;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index()
    {
        $clients = collect();
        $vehicles = collect();
        $technicians = Technician::all();
        $partsPrefill = [];

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'quotation')
            ->orderByDesc('created_at')
            ->limit(CashierListLimits::QUOTATION_SO_APPOINTMENT_HISTORY)
            ->get();

        return view('cashier.quotation', compact('clients', 'vehicles', 'technicians', 'history', 'partsPrefill'));
    }

    public function create()
    {
        $clients = collect();
        $vehicles = collect();
        $technicians = Technician::all();
        $history = collect([]);
        $invoice = null;
        $partsPrefill = [];

        return view('cashier.quotation', compact('invoice', 'clients', 'vehicles', 'technicians', 'history', 'partsPrefill'));
    }


    // Store a new quotation (invoice)
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
            'total_discount' => 'required|numeric',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_type' => 'required|string',
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0',
            'number' => 'nullable|string',
            'address' => 'nullable|string',




        ]);



        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);
        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $clientId, $vehicleId, $manualCustomer) {
            $invoice = Invoice::create([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'vehicle_name' => null,
                'source_type' => 'quotation',
                'service_status' => 'pending',
                'status' => 'unpaid',
                'subtotal' => $request->subtotal,
                'total_discount' => $request->total_discount,
                'vat_amount' => $request->vat_amount,
                'grand_total' => $request->grand_total,
                'payment_type' => $request->payment_type,
                'payment_cash_amount' => $request->filled('payment_cash_amount') ? $request->payment_cash_amount : null,
                'payment_non_cash_amount' => $request->filled('payment_non_cash_amount') ? $request->payment_non_cash_amount : null,
                'number' => $request->number,
                'address' => $request->address,
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $original = $item['original_price'] ?? ($item['manual_selling_price'] ?? 0);
                    $discount = $item['discount_value'] ?? 0;
                    $effectivePrice = $original - $discount;

                    $qty = $item['quantity'] ?? 0;
                    $lineTotal = $qty * $effectivePrice;

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

            return $invoice;
        });

        return redirect()->route('cashier.quotation.index')->with('success', 'Quotation created!');
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
        $technicians = Technician::all();
        $partsPrefill = $this->partsPrefillForLineItems($invoice);

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'quotation')
            ->orderByDesc('created_at')
            ->limit(CashierListLimits::QUOTATION_SO_APPOINTMENT_HISTORY)
            ->get();

        return view('cashier.quotation', compact('invoice', 'clients', 'vehicles', 'technicians', 'history', 'partsPrefill'));
    }

    /**
     * Paginated part search for quotation line items (avoid loading full inventory into the page).
     */
    public function ajaxParts(Request $request)
    {
        return Inventory::partPickerAjaxResponse($request);
    }

    /** @return array<string, array<string, mixed>> */
    protected function partsPrefillForLineItems(Invoice $invoice): array
    {
        return Inventory::partPickerPrefillByIds($invoice->items->pluck('part_id'));
    }

    // Update existing quotation (invoice) or just its source_type
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Fast update for just the source_type
        if ($request->has('quick_update') && $request->has('source_type')) {
            $invoice->update([
                'source_type' => $request->source_type
            ]);
            return redirect()->route('cashier.quotation.index')->with('success', 'Status updated!');
        }

        // Full update (from form)
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
            'total_discount' => 'required|numeric',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_type' => 'required|string',
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0',
            'number' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);
        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $invoice, $clientId, $vehicleId, $manualCustomer) {
            $invoice->update([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'vehicle_name' => null,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'source_type' => 'quotation',
                'service_status' => 'pending',
                'status' => 'unpaid',
                'subtotal' => $request->subtotal,
                'total_discount' => $request->total_discount,
                'vat_amount' => $request->vat_amount,
                'grand_total' => $request->grand_total,
                'payment_type' => $request->payment_type,
                'payment_cash_amount' => $request->filled('payment_cash_amount') ? $request->payment_cash_amount : null,
                'payment_non_cash_amount' => $request->filled('payment_non_cash_amount') ? $request->payment_non_cash_amount : null,
                'number' => $request->number,
                'address' => $request->address,
            ]);

            $invoice->items()->delete();
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $original = $item['original_price'] ?? ($item['manual_selling_price'] ?? 0);
                    $discount = $item['discount_value'] ?? 0;
                    $effectivePrice = $original - $discount;

                    $qty = $item['quantity'] ?? 0;
                    $lineTotal = $qty * $effectivePrice;

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
                    $invoice->jobs()->create([
                        'job_description' => $job['job_description'] ?? '',
                        'technician_id' => $job['technician_id'] ?? null,
                        'total' => $job['total'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('cashier.quotation.index')->with('success', 'Quotation updated!');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->jobs()->delete();
            $invoice->delete();
        });

        return redirect()->route('cashier.quotation.index')->with('success', 'Quotation deleted!');
    }

    public function view($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        return view('cashier.quotation-view', compact('invoice'));
    }

    public function ajaxSearch(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 10;

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

        $total = (clone $query)->count();

        $clients = (clone $query)
            ->orderForSelect2Dropdown()
            ->forPage($page, $perPage)
            ->get();

        $ids = $clients->pluck('id')->all();
        $invNames = Client::latestInvoiceCustomerNameByClientId($ids);
        $plates = Client::primaryVehiclePlateByClientId($ids);

        $results = $clients->map(fn (Client $c) => [
            'id' => $c->id,
            'text' => $c->select2Label($invNames->get($c->id), $plates->get($c->id)),
            'phone' => $c->phone,
            'address' => $c->address,
        ])->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }


}
