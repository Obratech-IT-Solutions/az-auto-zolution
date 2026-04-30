<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesReportExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoicePaymentAllocation;
use App\Support\SalesReportLineBuilder;
use Carbon\Carbon;
// Import for export
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $invoices = Invoice::with(['items.part', 'client', 'vehicle', 'jobs'])
            ->where('status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $allItems = SalesReportLineBuilder::flattenedItems($invoices);

        // ─── AGGREGATES ───────────────────────────────────────────────────────
        // Gross = sum of line/job rows (before invoice-level discount).
        // Billed revenue = Σ grand_total (matches cashier; invoice discount already applied).
        $grossSalesLines = (float) collect($allItems)->sum('line_total');

        $lineDiscountPesos = $invoices->sum(function (Invoice $inv) {
            return $inv->items->sum(function ($item) {
                return (float) $item->quantity * (float) $item->discount_value;
            });
        });
        $invoiceDiscountPesos = (float) $invoices->sum('total_discount');

        $totalSales = (float) $invoices->sum(fn (Invoice $inv) => SalesReportLineBuilder::billedAmount($inv));

        $totalCost = collect($allItems)
            ->sum(fn ($item) => ($item['acquisition_price'] ?? 0)
                * ($item['quantity'] ?? 1)
            );
        $totalProfit = $totalSales - $totalCost;

        $cashSales = 0.0;
        $nonCashSales = 0.0;
        foreach ($invoices as $inv) {
            $billable = SalesReportLineBuilder::billedAmount($inv);
            $alloc = InvoicePaymentAllocation::cashAndCashlessForInvoice($inv, $billable);
            $cashSales += $alloc['cash'];
            $nonCashSales += $alloc['cashless'];
        }

        $dailySummaries = SalesReportLineBuilder::dailySummaries($invoices, $allItems);

        return view('admin.sales-report', [
            'invoices' => $invoices,
            'allItems' => $allItems,
            'totalSales' => $totalSales,
            'grossSalesLines' => $grossSalesLines,
            'lineDiscountPesos' => $lineDiscountPesos,
            'invoiceDiscountPesos' => $invoiceDiscountPesos,
            'totalCost' => $totalCost,
            'totalProfit' => $totalProfit,
            'cashSales' => $cashSales,
            'nonCashSales' => $nonCashSales,
            'dailySummaries' => $dailySummaries,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $invoices = Invoice::with(['items.part', 'client', 'vehicle', 'jobs'])
            ->where('status', 'paid')
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $allItems = SalesReportLineBuilder::flattenedItems($invoices);

        return Excel::download(
            new SalesReportExport($allItems, $startDate, $endDate, $invoices),
            'Sales_Report_'.$startDate.'_to_'.$endDate.'.xlsx'
        );
    }
}
