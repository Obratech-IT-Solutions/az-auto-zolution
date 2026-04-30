@extends('layouts.cashier')

@section('title', 'Invoice Details')

@section('content')
@php
    // Filter out zero-value lines
    $items = $invoice->items->filter(function($i) {
        return $i->line_total > 0;
    });
    $jobs = $invoice->jobs->filter(function($j) {
        return $j->total > 0;
    });

    // Sum materials (items) and labor (jobs)
    $materials   = $items->sum('line_total');
    $labor_total = $jobs->sum('total');

    // Grand total (VAT-inclusive)
    $total_sales = $materials + $labor_total;

    // Reverse-calculate VAT (12%) on the full amount
    $net_of_vat = $total_sales / 1.12;
    $vat_amount = $total_sales - $net_of_vat;

    // Net Sales after any discount
    $net_sales = $total_sales - $invoice->total_discount;
@endphp


<style>
@media print {
  body * { visibility: hidden!important; }
  #invoice-print, #invoice-print * { visibility: visible!important; }
  #invoice-print {
    position: absolute; top:0; left:26%; transform:translateX(-50%);
    width:100vw; height:100vh; margin:0; padding:0;
    background:white!important; box-sizing:border-box; overflow:hidden;
  }
  .no-print, .no-print * { display:none!important; }
  @page { margin:0; size:A4; }
  html, body { margin:0; padding:0; width:100%; height:100%; overflow:visible; }
  .invoice-header-bar, .stripe-bar .stripe, .details-table .label,
  .invoice-table th, .invoice-table tfoot tr td,
  .labor-material-table th, .labor-material-table tfoot td,
  .job-table th, .job-table tfoot td
  { -webkit-print-color-adjust: exact!important; print-color-adjust: exact!important; }
}

.invoice-main { border:1px solid #eee; background:#fff; max-width:900px; margin:0 auto; font-size:15px; overflow:hidden; }
.invoice-header-bar {
  background: #FFD71A;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 100px;           /* Set a fixed height */
  padding: 0 24px;        /* Remove top/bottom padding */
  overflow: hidden;       /* Cut off overflow (if any) */
}
.invoice-header-bar .logo {
  max-height: 250px;
  height: auto;
}
.invoice-header-bar h1 { font-size:2rem; margin:0; font-weight:bold; }
.stripe-bar { display:flex; height:8px; }
.stripe { flex:1; }
.stripe.red   { background:#E40000; }
.stripe.green { background:#008000; }
.stripe.black { background:#000; }
.company-info { padding:16px 24px; font-family:Arial,sans-serif; }
.company-info h2 { margin:0; font-size:1.5rem; font-weight:bold; }
.company-info em { display:block; margin-bottom:8px; font-style:italic; }
.company-info p { margin:2px 0; line-height:1.3; }
.details-section { display:grid; grid-template-columns:2fr 1fr; gap:16px; padding:0 20px 20px; }
.details-table, .right-details-table { width:100%; border-collapse:collapse; }
.details-table td, .right-details-table td { padding:4px 10px; border:1px solid #c5c5c5; background:#f9f9f9; font-size:0.8rem; }
.details-table .label { background:#FFD71A; font-weight:bold; text-transform:uppercase; width:35%; }
.right-details-table td:first-child { background:#FFD71A; text-align:left; font-weight:bold; }
.right-details-table td:last-child { text-align:right; }
.right-details-table .invoice-no { color:#E40000; font-size:0.8rem; }

/* Tables */
.invoice-table, .labor-material-table, .job-table, .totals-table {
  width: 95%; margin: 20px auto 0; border-collapse: collapse;
}
.invoice-table th, .invoice-table td,
.labor-material-table th, .labor-material-table td,
.job-table th, .job-table td {
  border:1px solid #ccc; padding:4px; font-size:0.8rem;
}
.invoice-table th, .labor-material-table th, .job-table th {
  background:#FFD71A; font-weight:bold; text-align:left;
}
.invoice-table th:nth-child(3), .invoice-table td:nth-child(3),
.invoice-table th:nth-child(4), .invoice-table td:nth-child(4),
.labor-material-table th:nth-child(3), .labor-material-table td:nth-child(3),
.labor-material-table th:nth-child(4), .labor-material-table td:nth-child(4),
.job-table th:nth-child(3), .job-table td:nth-child(3),
.job-table th:nth-child(4), .job-table td:nth-child(4) {
  text-align:right!important;
}
.labor-material-table tfoot td, .job-table tfoot td {
  background:#FFD71A; font-weight:bold;
}
.totals-table td { padding:4px 8px; font-size:0.8rem; }
.totals-table td:first-child { text-align:left; }
.totals-table td:last-child  { text-align:right; }
.signature { text-align:center; margin-top:10px; font-weight:bold; }
</style>

<div class="container mt-4">
  <div class="no-print mb-2">
    <a href="{{ route('cashier.appointment.index') }}" class="btn btn-sm btn-secondary">← Back to Appointment</a>
    <button onclick="printInvoice()" class="btn btn-sm btn-warning float-right">🖨 Print</button>
  </div>

  <div id="invoice-print" class="invoice-main">
    {{-- HEADER --}}
    <div class="invoice-header-bar">
      <img src="{{ asset('images/logo-print.png') }}" class="logo" alt="AZ Zolutions Logo">
      <h1>SERVICE APPOINTMENT</h1>
    </div>

    {{-- COLOR STRIPES --}}
    <div class="stripe-bar">
      <div class="stripe red"></div>
      <div class="stripe green"></div>
      <div class="stripe black"></div>
    </div>

    {{-- COMPANY INFO --}}
    <div class="company-info">
      <h2>AZ AUTOMOTIVE ZOLUTIONS</h2>
      <em>CAR CARE CENTER</em>
      <p>
        Corner Kia Street, Bagay Road<br>
        Tuguegarao City, Cagayan 3500<br>
        TIN No: 117-688-743-00000<br>
        Tel. No: 396-4032 / 0917-578-0347
      </p>
    </div>

    {{-- DETAILS GRID --}}
    <div class="details-section">
      <table class="details-table">
        <tr><td class="label">Name</td><td>{{ $invoice->resolvedCustomerName() }}</td></tr>
        <tr><td class="label">Plate No</td><td>{{ $invoice->vehicle->plate_number ?? $invoice->vehicle_name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Manufacturer</td><td>{{ $invoice->vehicle->manufacturer ?? 'N/A' }}</td></tr>
        <tr><td class="label">Model</td><td>{{ $invoice->vehicle->model ?? 'N/A' }}</td></tr>
        <tr><td class="label">Year</td><td>{{ $invoice->vehicle->year ?? 'N/A' }}</td></tr>
        <tr><td class="label">Color</td><td>{{ $invoice->vehicle->color ?? 'N/A' }}</td></tr>
        <tr>
          <td class="label">Odometer</td>
          <td style="color:#E40000">{{ $invoice->vehicle->odometer ?? '0' }}</td>
        </tr>
      </table>
      <table class="right-details-table">
        <tr><td>Service Invoice No.</td><td class="invoice-no">{{ $invoice->invoice_no }}</td></tr>
        <tr><td>Date</td><td>{{ $invoice->created_at->format('d/m/Y') }}</td></tr>
        <tr><td>Receive Time</td><td>{{ $invoice->created_at->format('g:i a') }}</td></tr>
        <tr>
  <td>Address</td>
  <td style="white-space:pre-line">
    {{ $invoice->resolvedCustomerAddress() ?? '' }}
  </td>
</tr>
<tr>
  <td>Contact No.</td>
  <td>
    {{ $invoice->resolvedCustomerPhone() ?? '' }}
  </td>
</tr>
      </table>
    </div>

    @include('partials.invoice-processor-meta', ['invoice' => $invoice])

    {{-- ITEMS --}}
    <table class="invoice-table">
      <thead>
        <tr>
          <th>Quantity</th>
          <th>Description</th>
          <th>Unit Price</th>
          <th>Line Total</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $item)
        <tr>
          <td>{{ $item->quantity }}</td>
          <td> {{ $item->manual_part_name ?? $item->part?->item_name ?? ''}}</td>
          <td>₱{{ number_format($item->discounted_price ?? $item->original_price, 2) }}</td>
          <td>₱{{ number_format($item->line_total, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    {{-- LABOR MATERIALS (yellow, single row) --}}
    <table class="labor-material-table">
      <tr>
        <th colspan="2">Labor</th>
        <th>Total Material</th>
        <th>₱{{ number_format($materials, 2) }}</th>
      </tr>
      @foreach($jobs as $job)
      <tr>
        <td>{{ strtoupper($job->technician->name ?? '-') }}</td>
        <td>{{ $job->job_description }}</td>
        <td></td>
        <td>{{ $job->total ? '₱'.number_format($job->total, 2) : '' }}</td>
      </tr>
      @endforeach
    </table>

    {{-- Job Table and totals --}}
    <table class="job-table">
     <tr>
  <td colspan="2"></td>
  <td><strong>Total Sales</strong></td>
  <td><strong>₱{{ number_format($total_sales, 2) }}</strong></td>
</tr>
<tr>
  <td colspan="2">Net of VAT</td>
  <td></td>
  <td>₱{{ number_format($net_of_vat, 2) }}</td>
</tr>
<tr>
  <td colspan="2">VAT (12%)</td>
  <td></td>
  <td>₱{{ number_format($vat_amount, 2) }}</td>
</tr>
<tr>
  <td colspan="2"></td>
  <td><strong>Net Sales</strong></td>
  <td><strong>₱{{ number_format($net_sales, 2) }}</strong></td>
</tr>

    </table>

    {{-- Client’s name centered --}}
    <div class="text-center mt-4">
      <strong>{{ strtoupper($invoice->resolvedCustomerName()) }}</strong>
    </div>
    <div class="signature">CUSTOMER NAME & SIGNATURE</div>
  </div>
</div>

<script>
  function printInvoice() {
    window.print();
  }
</script>
@endsection
