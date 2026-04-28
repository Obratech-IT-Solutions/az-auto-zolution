<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Support\CashierListLimits;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'vehicle'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('client', fn($c) => $c->where('name', 'like', "%$s%"))
                    ->orWhereHas('vehicle', fn($v) => $v->where('plate_number', 'like', "%$s%"))
                    ->orWhere('customer_name', 'like', "%$s%")
                    ->orWhere('vehicle_name', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%")
                    ->orWhere('source_type', 'like', "%$s%");
            });
        }

        $history = $query->paginate(CashierListLimits::HISTORY_INDEX_PER_PAGE)->withQueryString();

        return view('cashier.history', compact('history'));
    }

    public function view($id)
    {
        $record = \App\Models\Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs.technician'
        ])->findOrFail($id);

        return view('cashier.history-view', compact('record'));
    }


    public function json($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);

        return response()->json(['invoice' => $invoice]);
    }
}
