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
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    public function index()
    {
        $clients = collect();
        $vehicles = collect();
        $parts = Inventory::selectForLineItems();
        $technicians = Technician::all();

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'service_order')
            ->latest('created_at')
            ->limit(CashierListLimits::QUOTATION_SO_APPOINTMENT_HISTORY)
            ->get();

        return view('cashier.service-order', compact('clients', 'vehicles', 'parts', 'technicians', 'history'));
    }

    public function create()
    {
        $clients = collect();
        $vehicles = collect();
        $parts = Inventory::selectForLineItems();
        $technicians = Technician::all();
        $history = collect([]);

        return view('cashier.service-order', compact('clients', 'vehicles', 'parts', 'technicians', 'history'));
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
            'payment_type' => 'required|string',
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0'
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
                'vehicle_name' => $vehicleId ? null : $request->vehicle_name,
                'source_type' => 'service_order',
                'service_status' => 'pending',
                'status' => 'unpaid',
                'subtotal' => 0,
                'total_discount' => 0,
                'vat_amount' => 0,
                'grand_total' => 0,
                'payment_type' => $request->payment_type,
                'payment_cash_amount' => $request->filled('payment_cash_amount') ? $request->payment_cash_amount : null,
                'payment_non_cash_amount' => $request->filled('payment_non_cash_amount') ? $request->payment_non_cash_amount : null,
                'number' => $request->number,
                'address' => $request->address,
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $invoice->items()->create([
                        'part_id' => $item['part_id'] ?? null,
                        'manual_part_name' => $item['manual_part_name'] ?? null,
                        'manual_serial_number' => $item['manual_serial_number'] ?? null,
                        'manual_acquisition_price' => $item['manual_acquisition_price'] ?? null,
                        'manual_selling_price' => $item['manual_selling_price'] ?? null,
                        'quantity' => $item['quantity'],
                        'original_price' => $item['original_price'] ?? ($item['manual_selling_price'] ?? 0),
                        'line_total' => $item['quantity'] * ($item['original_price'] ?? ($item['manual_selling_price'] ?? 0)),
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
        });

        return redirect()->route('cashier.serviceorder.index')->with('success', 'Service Order created!');
    }

    public function edit($id)
    {
        $invoice = Invoice::with(['items', 'jobs', 'client', 'vehicle'])->findOrFail($id);
        $clients = $invoice->client_id
            ? Client::where('id', $invoice->client_id)->get(['id', 'name'])
            : collect();
        $vehicles = $invoice->vehicle_id
            ? Vehicle::where('id', $invoice->vehicle_id)->get(['id', 'plate_number', 'model', 'client_id', 'year', 'color', 'odometer'])
            : collect();
        $parts = Inventory::selectForLineItems();
        $technicians = Technician::all();

        $history = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'service_order')
            ->latest('created_at')
            ->limit(CashierListLimits::QUOTATION_SO_APPOINTMENT_HISTORY)
            ->get();

        return view('cashier.service-order', compact('invoice', 'clients', 'vehicles', 'parts', 'technicians', 'history'));
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Fast update for just the source_type
        if ($request->has('quick_update') && $request->has('source_type')) {
            $invoice->update([
                'source_type' => $request->source_type
            ]);
            return redirect()->route('cashier.serviceorder.index')->with('success', 'Status updated!');
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
            'total_discount' => 'required|numeric',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'payment_type' => 'required|string',
            'payment_cash_amount' => 'nullable|numeric|min:0',
            'payment_non_cash_amount' => 'nullable|numeric|min:0',
            'number' => 'nullable|string',
            'address' => 'nullable|string',

        ]);

        if ($request->customer_name || $request->vehicle_name) {
            $clientId = null;
            $vehicleId = null;
        } else {
            $resolver = app(ClientVehicleResolver::class);
            $clientId = $resolver->resolveClientId($request);
            $vehicleId = $resolver->resolveVehicleId($request, $clientId);
        }

        DB::transaction(function () use ($request, $invoice, $clientId, $vehicleId) {
            $invoice->update([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $request->customer_name,
                'vehicle_name' => $request->vehicle_name,
                'source_type' => 'service_order',
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
                    $invoice->items()->create([
                        'part_id' => $item['part_id'] ?? null,
                        'manual_part_name' => $item['manual_part_name'] ?? null,
                        'manual_serial_number' => $item['manual_serial_number'] ?? null,
                        'manual_acquisition_price' => $item['manual_acquisition_price'] ?? null,
                        'manual_selling_price' => $item['manual_selling_price'] ?? null,
                        'quantity' => $item['quantity'],
                        'original_price' => $item['original_price'] ?? ($item['manual_selling_price'] ?? 0),
                        'line_total' => $item['quantity'] * ($item['original_price'] ?? ($item['manual_selling_price'] ?? 0)),
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

        return redirect()->route('cashier.serviceorder.index')->with('success', 'Service Order updated!');
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->jobs()->delete();
            $invoice->delete();
        });

        return redirect()->route('cashier.serviceorder.index')->with('success', 'Service Order deleted!');
    }

    public function view($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        return view('cashier.service-order-view', compact('invoice'));
    }
    public function show($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        // This assumes you want to reuse the view for showing details
        return view('cashier.service-order-view', compact('invoice'));
    }

    public function ajaxClients(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
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

        $results = $query
            ->orderForSelect2Dropdown()
            ->paginate($perPage, ['id', 'name', 'phone', 'address', 'email'], 'page', $page);

        $ids = $results->pluck('id')->all();
        $invNames = Client::latestInvoiceCustomerNameByClientId($ids);
        $plates = Client::primaryVehiclePlateByClientId($ids);

        return response()->json([
            'results' => $results->map(function (Client $client) use ($invNames, $plates) {
                $plate = $plates->get($client->id);

                return [
                    'id' => $client->id,
                    'text' => $client->select2Label($invNames->get($client->id), $plate),
                    'address' => $client->address,
                    'number' => $client->phone,
                    'plate' => $plate,
                ];
            })->values(),
            'pagination' => [
                'more' => $results->hasMorePages(),
            ],
        ]);
    }


}
