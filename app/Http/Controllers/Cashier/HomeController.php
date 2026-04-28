<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Inventory;
use App\Support\CashierListLimits;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    private const PERIOD_ALL = 'all';

    private const ALLOWED_PERIODS = ['all', 'day', 'week', 'month', 'year'];

    public function index(Request $request)
    {
        [$period, $refDateInput, $rangeStart, $rangeEnd, $rangeLabel] = $this->resolveDashboardPeriod($request);

        $invoiceInRange = Invoice::query();
        if ($period !== self::PERIOD_ALL && $rangeStart && $rangeEnd) {
            $invoiceInRange->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }

        $quotationCount = (clone $invoiceInRange)->where('source_type', 'quotation')->count();
        $invoicingCount = (clone $invoiceInRange)->where('source_type', 'invoicing')->count();

        if ($period !== self::PERIOD_ALL && $rangeStart && $rangeEnd) {
            $appointmentCount = Invoice::query()
                ->where('source_type', 'appointment')
                ->whereNotNull('appointment_date')
                ->whereBetween('appointment_date', [$rangeStart, $rangeEnd])
                ->count();
        } else {
            $appointmentCount = Invoice::where('source_type', 'appointment')->count();
        }

        $serviceOrderCount = (clone $invoiceInRange)->where('source_type', 'service_order')->count();
        $historyCount = (clone $invoiceInRange)->count();

        $inventoryQuery = Inventory::query();
        if ($period !== self::PERIOD_ALL && $rangeStart && $rangeEnd) {
            $inventoryQuery->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        }
        $inventoryCount = $inventoryQuery->count();

        $appointmentQuery = Invoice::with(['client', 'vehicle'])
            ->where('source_type', 'appointment')
            ->whereNotNull('appointment_date');
        if ($period !== self::PERIOD_ALL && $rangeStart && $rangeEnd) {
            $appointmentQuery->whereBetween('appointment_date', [$rangeStart, $rangeEnd]);
        }
        $appointments = $appointmentQuery
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->limit(CashierListLimits::HOME_APPOINTMENTS_MAX)
            ->get();

        $events = $appointments->map(function ($appointment) {
            $plate = $appointment->vehicle?->plate_number;

            return [
                'title' => $appointment->resolvedCustomerName().($plate ? ' - '.$plate : ''),
                'start' => $appointment->appointment_date->format('Y-m-d'),
                'url' => route('cashier.appointment.edit', $appointment->id),
            ];
        });

        return view('cashier.home', compact(
            'quotationCount',
            'invoicingCount',
            'appointmentCount',
            'serviceOrderCount',
            'historyCount',
            'inventoryCount',
            'events',
            'period',
            'refDateInput',
            'rangeLabel'
        ));
    }

    /**
     * @return array{0: string, 1: string, 2: ?Carbon, 3: ?Carbon, 4: string}
     */
    private function resolveDashboardPeriod(Request $request): array
    {
        $period = $request->query('period', self::PERIOD_ALL);
        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = self::PERIOD_ALL;
        }

        $refRaw = $request->query('ref');
        try {
            $ref = $refRaw ? Carbon::parse($refRaw)->startOfDay() : Carbon::today();
        } catch (\Throwable $e) {
            $ref = Carbon::today();
        }

        $refDateInput = $ref->format('Y-m-d');

        if ($period === self::PERIOD_ALL) {
            return [$period, $refDateInput, null, null, 'All time'];
        }

        switch ($period) {
            case 'day':
                $start = $ref->copy()->startOfDay();
                $end = $ref->copy()->endOfDay();
                $label = $ref->format('M j, Y');
                break;
            case 'week':
                $start = $ref->copy()->startOfWeek();
                $end = $ref->copy()->endOfWeek();
                $label = $start->format('M j, Y').' – '.$end->format('M j, Y');
                break;
            case 'month':
                $start = $ref->copy()->startOfMonth();
                $end = $ref->copy()->endOfMonth();
                $label = $ref->format('F Y');
                break;
            case 'year':
                $start = $ref->copy()->startOfYear();
                $end = $ref->copy()->endOfYear();
                $label = $ref->format('Y');
                break;
            default:
                return [$period, $refDateInput, null, null, 'All time'];
        }

        return [$period, $refDateInput, $start, $end, $label];
    }
}

