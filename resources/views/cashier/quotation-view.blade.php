@extends('layouts.cashier')

@section('title', 'Invoice Details')

@section('content')
  @php
    $items = $invoice->items;
    $jobs = $invoice->jobs;

    $materials = $items->sum('line_total');
    $labor_total = $jobs->sum('total');
    $gross_total = $materials + $labor_total;
    $discounted_total = $gross_total - $invoice->total_discount;

    // Reverse-calculate VAT (12%) on the discounted total
    $net_of_vat = $discounted_total / 1.12;
    $vat_amount = $discounted_total - $net_of_vat;

    $net_sales = $discounted_total;
    $sourceType = strtolower((string) ($invoice->source_type ?? 'quotation'));
    $documentTitle = [
      'quotation' => 'QUOTATION',
      'appointment' => 'APPOINTMENT',
      'service_order' => 'SERVICE ORDER',
      'invoicing' => 'SERVICE INVOICE',
    ][$sourceType] ?? 'QUOTATION';
    @endphp




  <style>
    @media print {
    body * {
      visibility: hidden !important;
    }

    #invoice-print,
    #invoice-print * {
      visibility: visible !important;
    }

    #invoice-print {
      position: absolute;
      top: 0;
      left: 26%;
      transform: translateX(-50%);
      width: 94vw;
      height: 100vh;
      margin: 0;
      padding: 0;
      background: white !important;
      box-sizing: border-box;
      overflow: hidden;
    }

    .no-print,
    .no-print * {
      display: none !important;
    }

    @page {
      margin: 1cm;
      size: A4;
    }

    html,
    body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      overflow: visible;
    }

    #invoice-print,
    #invoice-print * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    }

    .invoice-main {
    border: 1px solid #eee;
    background: #fff;
    max-width: 900px;
    margin: 0 auto;
    font-size: 15px;
    overflow: hidden;
    }

    .invoice-header-bar {
    background: #FFD71A;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 100px;
    /* Set a fixed height */
    padding: 0 20px;
    /* Remove top/bottom padding */
    overflow: hidden;
    /* Cut off overflow (if any) */
    }

    .invoice-header-bar .logo {
    max-height: 250px;
    height: auto;
    }

    .invoice-header-bar h1 {
    font-size: 2rem;
    margin: 0;
    font-weight: bold;
    }

    .stripe-bar {
    display: flex;
    height: 8px;
    }

    .stripe {
    flex: 1;
    }

    .stripe.red {
    background: #E40000;
    }

    .stripe.green {
    background: #008000;
    }

    .stripe.black {
    background: #000;
    }

    .company-info {
    padding: 16px 20px;
    font-family: Arial, sans-serif;
    }

    .company-info h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
    }

    .company-info em {
    display: block;
    margin-bottom: 8px;
    font-style: italic;
    }

    .company-info p {
    margin: 2px 0;
    line-height: 1.3;
    }

    .details-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    padding: 0 20px 20px;
    }

    .details-table,
    .right-details-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    }

    .details-table td,
    .right-details-table td {
    padding: 4px 10px;
    border: 1px solid #c5c5c5;
    background: #f9f9f9;
    font-size: 0.8rem;
    }

    .details-table .label {
    background: #FFD71A;
    font-weight: bold;
    text-transform: uppercase;
    width: 35%;
    }

    .right-details-table td:first-child {
    background: #FFD71A;
    text-align: left;
    font-weight: bold;
    }

    .right-details-table td:last-child {
    text-align: right;
    }

    .right-details-table .invoice-no {
    color: #E40000;
    font-size: 0.8rem;
    }

    /* Tables */
    .invoice-table,
    .labor-material-table,
    .job-table,
    .totals-table {
    width: calc(100% - 40px);
    margin: 20px 20px 0;
    border-collapse: collapse;
    table-layout: fixed;
    }

    .invoice-table th,
    .invoice-table td,
    .labor-material-table th,
    .labor-material-table td,
    .job-table th,
    .job-table td {
    border: 1px solid #ccc;
    padding: 4px;
    font-size: 0.8rem;
    }

    .invoice-table th,
    .labor-material-table th,
    .job-table th {
    background: #FFD71A;
    font-weight: bold;
    text-align: left;
    }

    .invoice-table th:nth-child(3),
    .invoice-table td:nth-child(3),
    .invoice-table th:nth-child(4),
    .invoice-table td:nth-child(4),
    .job-table th:nth-child(3),
    .job-table td:nth-child(3),
    .job-table th:nth-child(4),
    .job-table td:nth-child(4) {
    text-align: right !important;
    }

    .labor-material-table th:nth-child(4),
    .labor-material-table td:nth-child(4) {
    text-align: right !important;
    }

    .labor-material-table tfoot td,
    .job-table tfoot td {
    background: #FFD71A;
    font-weight: bold;
    }

    .totals-table td {
    padding: 4px 8px;
    font-size: 0.8rem;
    }

    .totals-table td:first-child {
    text-align: left;
    }

    .totals-table td:last-child {
    text-align: right;
    }

  </style>

  <div class="container mt-4">
    <div class="no-print mb-2">
    <a href="{{ route('cashier.quotation.index') }}" class="btn btn-sm btn-secondary">← Back to Quotation</a>
    <button onclick="printInvoice()" class="btn btn-sm btn-warning float-right">🖨 Print</button>
    </div>

    <div id="invoice-print" class="invoice-main">
    {{-- HEADER --}}
    <div class="invoice-header-bar">
      <img src="{{ asset('images/logo-print.png') }}" class="logo" alt="AZ Zolutions Logo">
      <h1>{{ $documentTitle }}</h1>
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
      <tr>
        <td class="label">Name</td>
        <td>{{ $invoice->resolvedCustomerName() }}</td>
      </tr>
      <tr>
        <td class="label">Plate No</td>
        <td>{{ $invoice->vehicle->plate_number ?? $invoice->vehicle_name ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="label">Manufacturer</td>
        <td>{{ $invoice->vehicle->manufacturer ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="label">Model</td>
        <td>{{ $invoice->vehicle->model ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="label">Year</td>
        <td>{{ $invoice->vehicle->year ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="label">Color</td>
        <td>{{ $invoice->vehicle->color ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="label">Odometer</td>
        <td style="color:#E40000">@if($invoice->vehicle && trim((string) ($invoice->vehicle->odometer ?? '')) !== ''){{ $invoice->vehicle->odometer }}@else N/A @endif</td>
      </tr>
      </table>
      <table class="right-details-table">
      <tr>
        <td>Service Invoice No.</td>
        <td class="invoice-no">{{ $invoice->invoice_no }}</td>
      </tr>
      <tr>
        <td>Date</td>
        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <td>Receive Time</td>
        <td>{{ $invoice->created_at->format('g:i a') }}</td>
      </tr>
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
      @include('partials.invoice-print-four-colgroup')
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
        <td>
        @if($item->manual_part_name)
      {{ $item->manual_part_name }}
      @elseif($item->part && $item->part->item_name)
      {{ $item->part->item_name }}
      @elseif($item->manual_selling_price)
      Manual Item
      @else
      -
      @endif
        </td>

        <td>
        ₱{{ number_format(($item->original_price - $item->discount_value), 2) }}
        @if($item->discount_value > 0)
      <small style="display:block;color:#888;">
        (₱{{ number_format($item->original_price, 2) }} - ₱{{ number_format($item->discount_value, 2) }})
      </small>
      @endif
        </td>

        <td>₱{{ number_format($item->line_total, 2) }}</td>
      </tr>
    @endforeach
      {{-- Total Material --}}
      <tr>
        <td colspan="3" style="text-align:right;font-weight:bold;background:#FFD71A;">Total Material</td>
        <td style="background:#FFD71A;font-weight:bold;">₱{{ number_format($materials, 2) }}</td>
      </tr>
      </tbody>
    </table>

    {{-- LABOR — 4 cols match materials / totals --}}
    <table class="labor-material-table">
      @include('partials.invoice-print-four-colgroup')
      <thead>
      <tr>
        <th>Technician</th>
        <th>Job Description</th>
        <th></th>
        <th>Total</th>
      </tr>
      </thead>
      <tbody>
      @foreach($jobs as $job)
      <tr>
      <td>{{ strtoupper($job->technician->name ?? '-') }}</td>
      <td>{{ $job->job_description }}</td>
      <td></td>
      <td>{{ $job->total ? '₱' . number_format($job->total, 2) : '' }}</td>
      </tr>
    @endforeach
      {{-- Total Labor --}}
      <tr>
        <td colspan="3" style="text-align:right;font-weight:bold;background:#FFD71A;">Total Labor</td>
        <td style="background:#FFD71A;font-weight:bold;text-align:right;">
        ₱{{ number_format($labor_total, 2) }}
        </td>
      </tr>
      </tbody>
    </table>


    {{-- Job Table and totals --}}
    <table class="job-table">
      @include('partials.invoice-print-four-colgroup')
      <tr>
      <td colspan="2"></td>
      <td><strong>Gross Sales</strong></td>
      <td><strong>₱{{ number_format($gross_total, 2) }}</strong></td>
      </tr>
      <tr>
      <td colspan="2">Less: Discount</td>
      <td></td>
      <td>- ₱{{ number_format($invoice->total_discount, 2) }}</td>
      </tr>
      <tr>
      <td colspan="2"></td>
      <td><strong>Total Sales</strong></td>
      <td><strong>₱{{ number_format($discounted_total, 2) }}</strong></td>
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
    </table>

    <footer class="invoice-print-footer">
      <div class="invoice-print-footer-balance" aria-hidden="true"></div>
      <div class="invoice-print-footer-main">
        <div class="customer-print-name">{{ strtoupper($invoice->resolvedCustomerName()) }}</div>
        <div class="signature">CUSTOMER NAME & SIGNATURE</div>
      </div>
      @include('partials.invoice-print-obratech-brand')
    </footer>
    </div>
  </div>

  <script>
    function printInvoice() {
    window.print();
    }
  </script>
@endsection