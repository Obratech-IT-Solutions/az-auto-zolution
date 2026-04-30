<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceHistoryController extends Controller
{
    /**
     * Display a paginated, searchable list of invoices grouped by date.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Invoice::with(['client', 'vehicle'])
            ->when($search, function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($q3) use ($search) {
                        $q3->where('plate_number', 'like', "%{$search}%");
                    });
            })
            ->orderBy('created_at', 'desc');

        $history = $query->paginate(25)->withQueryString();

        return view('admin.invoices', [
            'history' => $history,
            'search' => $search,
        ]);
    }

    /**
     * Show the detailed view of a single invoice.
     */
    public function show($id)
    {
        $invoice = Invoice::with([
            'client',
            'vehicle',
            'items.part',
            'jobs',
            'createdByUser',
            'lastProcessedByUser',
        ])->findOrFail($id);

        return view('admin.invoices-view', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Delete a given invoice.
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoiceNo = $invoice->invoice_no;
        $invoice->delete();

        return redirect()
            ->route('admin.invoices')
            ->with('success', "Invoice #{$invoiceNo} has been deleted.");
    }
}
