<?php

namespace App\Support;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared row flattening + daily rollups for admin sales report (index + Excel export).
 */
final class SalesReportLineBuilder
{
    /**
     * @param  Collection<int, Invoice>|iterable<int, Invoice>  $invoices
     * @return array<int, array<string, mixed>>
     */
    public static function flattenedItems(Collection|iterable $invoices): array
    {
        $allItems = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $allItems[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'date' => $invoice->created_at->format('Y-m-d'),
                    'customer_name' => $invoice->client->name
                                               ?? $invoice->customer_name
                                               ?? '-',
                    'vehicle_plate' => $invoice->vehicle?->plate_number ?? '',
                    'vehicle_manufacturer' => $invoice->vehicle?->manufacturer ?? '',
                    'vehicle_model' => $invoice->vehicle?->model ?? '',
                    'vehicle_year' => $invoice->vehicle?->year ?? '',
                    'item_name' => $item->manual_part_name
                                               ?? ($item->part->item_name ?? '-'),
                    'acquisition_price' => $item->manual_acquisition_price
                                               ?? ($item->part->acquisition_price ?? 0),
                    'selling_price' => $item->original_price,
                    'discount_value' => $item->discount_value,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                    'remarks' => $invoice->remarks ?? '',
                ];
            }

            foreach ($invoice->jobs as $job) {
                $allItems[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'date' => $invoice->created_at->format('Y-m-d'),
                    'customer_name' => $invoice->client->name ?? $invoice->customer_name ?? '-',
                    'vehicle_plate' => $invoice->vehicle?->plate_number ?? '',
                    'vehicle_manufacturer' => $invoice->vehicle?->manufacturer ?? '',
                    'vehicle_model' => $invoice->vehicle?->model ?? '',
                    'vehicle_year' => $invoice->vehicle?->year ?? '',
                    'item_name' => $job->job_description ?? '-',
                    'acquisition_price' => 0,
                    'selling_price' => $job->total ?? 0,
                    'discount_value' => 0,
                    'quantity' => 1,
                    'line_total' => $job->total ?? 0,
                    'remarks' => $invoice->remarks ?? '',
                ];
            }
        }

        return $allItems;
    }

    /** Same logic as grand total billed revenue (cashier grand_total, else lines+jobs). */
    public static function billedAmount(Invoice $invoice): float
    {
        $gt = (float) ($invoice->grand_total ?? 0);
        if ($gt > 0) {
            return $gt;
        }

        return (float) $invoice->items->sum('line_total') + (float) $invoice->jobs->sum('total');
    }

    /**
     * One pass per calendar day — used for yellow cards & table footers (no Blade filter-loops).
     *
     * @param  Collection<int, Invoice>  $invoices
     * @param  array<int, array<string, mixed>>  $allItems
     * @return Collection<int, array<string, mixed>>
     */
    public static function dailySummaries(Collection $invoices, array $allItems): Collection
    {
        $invoicesByDate = $invoices->groupBy(fn (Invoice $inv) => $inv->created_at->format('Y-m-d'));
        $itemsByDate = collect($allItems)->groupBy('date');

        return $itemsByDate->keys()->sort()->values()->map(function (string $date) use ($invoicesByDate, $itemsByDate) {
            $items = $itemsByDate[$date];
            /** @var Collection<int, Invoice> $dayInvs */
            $dayInvs = $invoicesByDate->get($date, collect());
            $daySales = (float) $dayInvs->sum(fn (Invoice $inv) => self::billedAmount($inv));
            $dayGrossLines = (float) collect($items)->sum('line_total');
            $dayCost = (float) collect($items)->sum(
                fn ($i) => ((float) ($i['acquisition_price'] ?? 0)) * ((float) ($i['quantity'] ?? 1))
            );
            $dayInvoiceDisc = (float) $dayInvs->sum('total_discount');
            $dayLineDisc = (float) $dayInvs->sum(fn (Invoice $inv) => $inv->items->sum(
                fn ($it) => (float) $it->quantity * (float) $it->discount_value
            ));

            $dayCash = 0.0;
            $dayNonCash = 0.0;
            foreach ($dayInvs as $inv) {
                $bill = self::billedAmount($inv);
                $alloc = InvoicePaymentAllocation::cashAndCashlessForInvoice($inv, $bill);
                $dayCash += $alloc['cash'];
                $dayNonCash += $alloc['cashless'];
            }

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('F d, Y'),
                'sales' => $daySales,
                'gross_lines' => $dayGrossLines,
                'cost' => $dayCost,
                'invoice_disc' => $dayInvoiceDisc,
                'line_disc' => $dayLineDisc,
                'cash' => $dayCash,
                'non_cash' => $dayNonCash,
                'profit' => $daySales - $dayCost,
            ];
        });
    }
}
