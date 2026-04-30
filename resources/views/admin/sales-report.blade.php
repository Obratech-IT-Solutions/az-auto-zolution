@extends('layouts.admin')
@section('title','Sales Report')

@section('content')
<style>
  .sales-report-daily-scroll {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.25rem;
  }
  .sales-report-daily-track {
    display: flex;
    flex-wrap: nowrap;
    gap: 1rem;
    width: max-content;
    min-width: 100%;
  }
  .sales-report-daily-card {
    flex: 0 0 auto;
    width: 16.5rem;
    max-width: 85vw;
  }
</style>
<div class="container-fluid px-2 px-md-4">
  <h2 class="mb-4 fw-bold">Sales Report</h2> 

  {{-- Export Button --}}
  <div class="mb-3">
    <a href="{{ route('admin.sales-report.export', ['start_date'=>$startDate,'end_date'=>$endDate]) }}"
       class="btn btn-success px-4 fw-bold" target="_blank">
       <i class="fas fa-file-excel me-1"></i> Export to Excel 
    </a>
  </div>

  {{-- Filter Form --}}
  <form method="GET" action="{{ route('admin.sales-report') }}"
        class="row g-3 align-items-end mb-4">
    <div class="col-md-auto">
      <label class="form-label mb-0">From:</label>
      <input type="date" name="start_date" value="{{ $startDate }}"
             class="form-control" required>
    </div>
    <div class="col-md-auto">
      <label class="form-label mb-0">To:</label>
      <input type="date" name="end_date" value="{{ $endDate }}"
             class="form-control" required>
    </div>
    <div class="col-md-auto">
      <button class="btn btn-warning px-4 fw-bold" type="submit">
        <i class="fas fa-filter me-1"></i> Filter
      </button>
    </div>
  </form>

  @php
    $byDate = collect($allItems)->groupBy('date');
    $dailyByDate = ($dailySummaries ?? collect())->keyBy('date');
  @endphp

  {{-- GROSS TOTAL (full width row) --}}
  <div class="mb-4">
    <div class="bg-info bg-opacity-10 border border-info rounded-3 p-3 shadow-sm" style="max-width: 36rem;">
      <h5 class="text-info fw-bold mb-3">
        GROSS TOTAL ({{ $startDate }} to {{ $endDate }})
      </h5>
      <div><b>Total sales (billed):</b> ₱{{ number_format($totalSales,   2) }}</div>
      <div class="text-muted small">Gross line total (before invoice discount): ₱{{ number_format($grossSalesLines, 2) }}</div>
      @if(($invoiceDiscountPesos ?? 0) > 0.005)
        <div class="text-muted small">Invoice-level discounts: −₱{{ number_format($invoiceDiscountPesos, 2) }}</div>
      @endif
      @if(($lineDiscountPesos ?? 0) > 0.005)
        <div class="text-muted small">Line item discounts (peso): −₱{{ number_format($lineDiscountPesos, 2) }}</div>
      @endif
      <div><b>Total Cost:</b>     ₱{{ number_format($totalCost,    2) }}</div>
      <div class="mt-2"><b>Payment Breakdown:</b></div>
      <div>&nbsp;&nbsp;Cash:      ₱{{ number_format($cashSales,   2) }}</div>
      <div>&nbsp;&nbsp;Non-Cash:  ₱{{ number_format($nonCashSales,2) }}</div>
      <div><b>Total Profit:</b>   ₱{{ number_format($totalProfit, 2) }}</div>
    </div>
  </div>

  {{-- Daily summaries: below gross total, horizontal scroll (no wrap) --}}
  @if(($dailySummaries ?? collect())->isNotEmpty())
    <h3 class="h6 fw-bold text-secondary mb-3">Daily summary <span class="text-muted fw-normal small">(scroll sideways)</span></h3>
    <div class="sales-report-daily-scroll mb-5">
      <div class="sales-report-daily-track">
        @foreach($dailySummaries as $d)
          <div class="bg-warning bg-opacity-10 border border-warning rounded-3 p-3 shadow-sm sales-report-daily-card">
            <div class="fw-semibold mb-2">
              <i class="fas fa-calendar-alt"></i>
              {{ $d['label'] }}
            </div>
            <div><b>Sales (billed):</b> ₱{{ number_format($d['sales'],    2) }}</div>
            @if($d['invoice_disc'] > 0.005 || $d['line_disc'] > 0.005)
              <div class="text-muted small">Gross lines ₱{{ number_format($d['gross_lines'], 2) }}
                @if($d['invoice_disc'] > 0.005) · Inv. disc −₱{{ number_format($d['invoice_disc'], 2) }}@endif
                @if($d['line_disc'] > 0.005) · Line disc −₱{{ number_format($d['line_disc'], 2) }}@endif
              </div>
            @endif
            <div><b>Cost:</b>     ₱{{ number_format($d['cost'],     2) }}</div>
            <div><b>Cash:</b>     ₱{{ number_format($d['cash'],     2) }}</div>
            <div><b>Non-Cash:</b> ₱{{ number_format($d['non_cash'], 2) }}</div>
            <div><b>Profit:</b>   ₱{{ number_format($d['profit'],   2) }}</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- Detailed per-day tables --}}
  @forelse($byDate as $date => $items)
    <h4 class="fw-bold text-warning mb-3">
      <i class="fas fa-calendar-day me-1"></i>
      {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
    </h4>

    <div class="table-responsive mb-5">
      <table class="table table-bordered align-middle shadow-sm mb-0">
        <thead class="bg-secondary bg-opacity-10 text-center">
          <tr>
            <th>Customer & Vehicle</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Acq. Price</th>
            <th>Sell Price</th>
            <th>Discount</th>
            <th>Line Total</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          @php $invoiceGroups = collect($items)->groupBy('invoice_id'); @endphp

          @foreach($invoiceGroups as $invId => $rows)
            @php
              $first            = $rows->first();
              $invNo            = $first['invoice_no'];
              $customer         = $first['customer_name'];
              $vehicleInfo      = "{$first['vehicle_manufacturer']} {$first['vehicle_model']} ({$first['vehicle_plate']}) {$first['vehicle_year']}";
              $groupInvs        = $invoices->where('id', $invId);
              $groupLineSum     = collect($rows)->sum('line_total');
              $groupInvoiceDisc = $groupInvs->sum('total_discount');
              $invModel         = $groupInvs->first();
              $clientTotal      = (float) ($invModel->grand_total ?? 0) > 0
                  ? (float) $invModel->grand_total
                  : ($groupLineSum - $groupInvoiceDisc);
            @endphp

            {{-- Invoice Header --}}
            <tr class="table-secondary">
              <td colspan="7">
                <strong>Invoice #{{ $invNo }} – {{ $customer }} – {{ $vehicleInfo }}</strong>
              </td>
              <td>
                <em>{{ $rows->first()['remarks'] }}</em>
              </td>
            </tr>

            {{-- Items/Jobs --}}
            @foreach($rows as $row)
              <tr>
                <td></td>
                <td>{{ $row['item_name'] }}</td>
                <td class="text-center">{{ $row['quantity'] }}</td>
                <td class="text-end">₱{{ number_format($row['acquisition_price'],2) }}</td>
                <td class="text-end">₱{{ number_format($row['selling_price'],    2) }}</td>
                <td class="text-end">₱{{ number_format($row['discount_value'],    2) }}</td>
                <td class="text-end">₱{{ number_format($row['line_total'],        2) }}</td>
                <td></td>
              </tr>
            @endforeach

            {{-- Invoice Totals --}}
            <tr>
              <td></td>
              <td colspan="5" class="text-end fw-bold">Discount (Invoice):</td>
              <td class="text-end">₱{{ number_format($groupInvoiceDisc, 2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td></td>
              <td colspan="5" class="text-end fw-bold">Client Total:</td>
              <td class="text-primary text-end">₱{{ number_format($clientTotal,    2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td></td>
              <td colspan="5" class="text-end fw-bold">Payment Type:</td>
              <td class="fw-semibold text-end">{{ \App\Support\InvoicePaymentAllocation::paymentBreakdownLabel($groupInvs->first()) }}</td>
              <td></td>
            </tr>
            <tr style="border-top:2px solid #ddd;">
              <td colspan="8"></td>
            </tr>
          @endforeach
        </tbody>

        @php
          $dayRow = $dailyByDate->get($date);
          $daySales       = (float) ($dayRow['sales'] ?? 0);
          $dayCost        = (float) ($dayRow['cost'] ?? collect($items)->sum(fn($i)=>((float)($i['acquisition_price']??0))*((float)($i['quantity']??1))));
          $dayInvoiceDisc = (float) ($dayRow['invoice_disc'] ?? 0);
          $dayLineDisc    = (float) ($dayRow['line_disc'] ?? 0);
          $dayCash        = (float) ($dayRow['cash'] ?? 0);
          $dayNonCash     = (float) ($dayRow['non_cash'] ?? 0);
          $dayProfit      = (float) ($dayRow['profit'] ?? ($daySales - $dayCost));
        @endphp
        <tfoot class="bg-light">
          <tr>
            <td colspan="5" class="text-end fw-bold">Total sales (billed):</td>
            <td></td>
            <td class="text-primary text-end">₱{{ number_format($daySales,  2) }}</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="5" class="text-end fw-bold">Total Cost:</td>
            <td></td>
            <td class="text-end">₱{{ number_format($dayCost,   2) }}</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="5" class="text-end fw-bold">Cash Sales:</td>
            <td></td>
            <td class="text-end">₱{{ number_format($dayCash,   2) }}</td>
            <td></td>
          </tr>
          <tr>
            <td colspan="5" class="text-end fw-bold">Non-Cash Sales:</td>
            <td></td>
            <td class="text-end">₱{{ number_format($dayNonCash,2) }}</td>
            <td></td>
          </tr>
          @if($dayInvoiceDisc > 0.005 || $dayLineDisc > 0.005)
          <tr>
            <td colspan="5" class="text-end text-muted">Invoice / line discounts (info):</td>
            <td></td>
            <td class="text-end text-muted">₱{{ number_format($dayInvoiceDisc + $dayLineDisc, 2) }}</td>
            <td></td>
          </tr>
          @endif
          <tr>
            <td colspan="5" class="text-end fw-bold">Total Profit:</td>
            <td></td>
            <td class="text-success text-end">₱{{ number_format($dayProfit,2) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  @empty
    <div class="alert alert-info">No paid sales in this period.</div>
  @endforelse

</div>
@endsection