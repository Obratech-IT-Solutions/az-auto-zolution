<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Vehicle;
use App\Services\ClientVehicleResolver;
use App\Support\InvoiceStaffStamp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    // Show the appointment creation page
    public function index()
    {
        $clients = collect();
        $vehicles = collect();

        // Fetch history of invoices related to clients and vehicles
        $history = Invoice::with(['client', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Build events for FullCalendar
        $events = [];
        foreach ($history as $h) {
            if ($h->appointment_date) {
                $events[] = [
                    'title' => ($h->client->name ?? $h->customer_name)
                        .($h->vehicle ? ' - '.$h->vehicle->plate_number : ''),
                    'start' => $h->appointment_date,
                    'url' => route('cashier.appointment.edit', $h->id),

                    'color' => match ($h->source_type) {
                        'cancelled' => '#dc3545',     // red
                        'service_order' => '#6c757d', // gray
                        'invoicing' => '#28a745',     // green
                        default => '#0dcaf0',         // cyan for appointments
                    },
                ];
            }
        }

        // Pass data to the view
        return view('cashier.appointment', compact('clients', 'vehicles', 'history', 'events'));
    }

    // Show the form to create a new appointment
    public function create()
    {
        $clients = collect();
        $vehicles = collect();
        $history = collect([]);
        $events = [];

        return view('cashier.appointment', compact('clients', 'vehicles', 'history', 'events'));
    }

    // Store a new (appointment)
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

            'appointment_date' => 'required|date',
            'note' => 'nullable|string',
        ], [
            'appointment_date.required' => 'Please pick an appointment date.',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);

        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $clientId, $vehicleId, $manualCustomer) {
            if ($request->filled('client_id') && $clientId) {
                Client::where('id', $clientId)->update([
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'address' => $request->input('address'),
                ]);
            }

            Invoice::create(array_merge([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'vehicle_name' => $vehicleId ? null : $request->vehicle_name,
                'source_type' => 'appointment',
                'service_status' => 'pending',
                'status' => 'unpaid',

                'appointment_date' => $request->appointment_date,
                'note' => $request->note,
            ], InvoiceStaffStamp::attributePairForCreate()));
        });

        return redirect()->route('cashier.appointment.index')->with('success', 'Appointment created!');
    }

    // Show the form for editing an existing quotation (appointment)
    public function edit($id)
    {
        $invoice = Invoice::with(['client', 'vehicle'])->findOrFail($id);
        $clients = $invoice->client_id
            ? Client::where('id', $invoice->client_id)->get(['id', 'name', 'phone', 'email', 'address'])
            : collect();
        $vehicles = $invoice->vehicle_id
            ? Vehicle::where('id', $invoice->vehicle_id)->get(['id', 'plate_number', 'model', 'client_id', 'year', 'color', 'odometer'])
            : collect();
        $history = Invoice::with(['client', 'vehicle'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Build events
        $events = [];
        foreach ($history as $h) {
            if ($h->appointment_date) {
                $events[] = [
                    'title' => ($h->client->name ?? $h->customer_name)
                        .($h->vehicle ? ' - '.$h->vehicle->plate_number : ''),
                    'start' => $h->appointment_date,
                    'url' => route('cashier.appointment.edit', $h->id),
                    'color' => match ($h->source_type) {
                        'cancelled' => '#dc3545',
                        'service_order' => '#6c757d',
                        'invoicing' => '#28a745',
                        default => '#0dcaf0',
                    },
                ];
            }
        }

        return view('cashier.appointment', compact('invoice', 'clients', 'vehicles', 'history', 'events'));
    }

    // Update an existing appointment
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Fast update for just the source_type
        if ($request->has('quick_update') && $request->has('source_type')) {
            $invoice->update(array_merge([
                'source_type' => $request->source_type,
            ], InvoiceStaffStamp::attributePairForUpdate()));

            return redirect()->route('cashier.appointment.index')->with('success', 'Status updated!');
        }

        // Validate
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

            'appointment_date' => 'required|date',
            'note' => 'nullable|string',
        ], [
            'appointment_date.required' => 'Please pick an appointment date.',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $resolver = app(ClientVehicleResolver::class);
        $clientId = $resolver->resolveClientId($request);

        $vehicleId = $resolver->resolveVehicleId($request, $clientId);

        $manualCustomer = trim((string) $request->input('customer_name', ''));
        if (Client::isPlaceholderLabel($manualCustomer)) {
            $manualCustomer = '';
        }

        DB::transaction(function () use ($request, $invoice, $clientId, $vehicleId, $manualCustomer) {
            if ($request->filled('client_id') && $clientId) {
                Client::where('id', $clientId)->update([
                    'phone' => $request->input('phone'),
                    'email' => $request->input('email'),
                    'address' => $request->input('address'),
                ]);
            }

            $invoice->update(array_merge([
                'client_id' => $clientId,
                'vehicle_id' => $vehicleId,
                'customer_name' => $manualCustomer !== '' ? $manualCustomer : null,
                'vehicle_name' => $vehicleId ? null : $request->vehicle_name,
                'source_type' => 'appointment',
                'service_status' => 'pending',
                'status' => 'unpaid',

                'appointment_date' => $request->appointment_date,
                'note' => $request->note,
            ], InvoiceStaffStamp::attributePairForUpdate()));
        });

        return redirect()->route('cashier.appointment.index')->with('success', 'Appointment updated!');
    }

    // Delete an appointment
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->delete();

        return redirect()->route('cashier.appointment.index')->with('success', 'Appointment deleted!');
    }

    // View an appointment (appointment)
    public function view($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician',
            'createdByUser',
            'lastProcessedByUser',
        ])->findOrFail($id);

        return view('cashier.appointment-view', compact('invoice'));
    }
}
