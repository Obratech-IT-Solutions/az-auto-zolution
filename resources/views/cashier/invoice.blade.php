@extends('layouts.cashier')

@section('title', isset($invoice) ? 'Edit Invoice' : 'New Invoice')

@section('content')
  @php
    $filteredUnpaid = $history->where('source_type', 'invoicing')
      ->where('created_at', '>=', now()->subHours(48))
      ->where('status', 'unpaid');

    $filteredPaid = $history->where('source_type', 'invoicing')
      ->where('created_at', '>=', now()->subHours(48))
      ->where('status', 'paid');

    $groupedUnpaid = $filteredUnpaid->sortByDesc('created_at')->values()->groupBy(function ($item) {
      return \Carbon\Carbon::parse($item->created_at)->format('F d, Y');
    });
    $groupedPaid = $filteredPaid->sortByDesc('created_at')->values()->groupBy(function ($item) {
      return \Carbon\Carbon::parse($item->created_at)->format('F d, Y');
    });

    $invListBadgeClass = [
      'quotation' => 'bg-secondary text-white',
      'cancelled' => 'bg-dark text-white',
      'appointment' => 'inv-badge-outline',
      'service_order' => 'bg-secondary text-white',
      'invoicing' => 'bg-primary text-white',
    ];
    $invListStatusBadge = [
      'unpaid' => 'bg-secondary text-white',
      'paid' => 'bg-primary text-white',
      'cancelled' => 'bg-dark text-white',
      'voided' => 'bg-dark text-white',
    ];
  @endphp

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    /* Cap width: avoid full-viewport stretch (inline 95vw was overriding modal-xl) */
    #invoiceModal .modal-dialog {
    max-width: min(1140px, calc(100vw - 2rem));
    margin-left: auto;
    margin-right: auto;
    }

    #invoiceModal .select2-container {
    width: 100% !important;
    max-width: 100%;
    }

    #invoiceModal .select2-container--open {
    z-index: 100999 !important;
    }

    /* Select2 — invoicing modal: white / black / blue (#4a90e2), matches service-order client feel */
    #invoiceModal {
      --inv-select2-accent: #4a90e2;
      --inv-select2-border: #ced4da;
      --inv-select2-text: #212529;
      --inv-select2-muted: #6c757d;
      --inv-theme-blue: #4a90e2;
    }

    /* Invoice modal + payment confirm — section heads and accents: blue / white / black only */
    #invoiceModal .inv-section-header,
    #invoiceConfirmPaymentModal .inv-section-header-mini {
      background-color: var(--inv-theme-blue) !important;
      color: #ffffff !important;
      border-bottom: none;
    }

    #invoiceConfirmPaymentModal .inv-section-header-mini {
      position: relative;
    }

    #invoiceConfirmPaymentModal .inv-section-header-mini .btn-close {
      filter: invert(1) grayscale(100%) brightness(2);
      opacity: 0.85;
    }

    #invoiceModal .inv-payment-card {
      border-color: #dee2e6 !important;
    }

    #invoiceModal .inv-payment-select {
      background-color: #ffffff !important;
    }

    /* Index list: muted badge outline (avoid sky blue / yellow) */
    .inv-badge-outline {
      color: var(--inv-theme-blue, #4a90e2) !important;
      background-color: #ffffff !important;
      border: 1px solid var(--inv-theme-blue, #4a90e2) !important;
      font-weight: 600;
    }

    #invoiceModal .select2-dropdown {
    z-index: 100999 !important;
    max-width: min(100vw - 32px, 640px);
    border: 1px solid var(--inv-select2-border);
    border-radius: 0.375rem;
    background: #ffffff;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    #invoiceModal .select2-container--default .select2-selection--single {
    background: #ffffff;
    border: 1px solid var(--inv-select2-border);
    border-radius: 0.375rem;
    min-height: 2.375rem;
    }

    #invoiceModal .select2-container--default.select2-container--focus .select2-selection--single,
    #invoiceModal .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--inv-select2-accent);
    box-shadow: 0 0 0 0.15rem rgba(74, 144, 226, 0.2);
    }

    #invoiceModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--inv-select2-text);
    line-height: 1.4;
    padding-left: 0.65rem;
    }

    #invoiceModal .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: var(--inv-select2-muted);
    }

    #invoiceModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 2.25rem;
    }

    #invoiceModal .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #495057 transparent transparent transparent;
    }

    #invoiceModal .select2-results__option {
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: normal;
    background: #ffffff;
    color: var(--inv-select2-text);
    }

    #invoiceModal .select2-results__option--disabled {
    color: var(--inv-select2-muted) !important;
    background: #ffffff !important;
    }

    /* Keyboard / hover row: blue bar, white text */
    #invoiceModal .select2-results__option--highlighted.select2-results__option--selectable,
    #invoiceModal .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--inv-select2-accent) !important;
    color: #ffffff !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted .text-danger {
    color: #ffffff !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted .text-muted {
    color: rgba(255, 255, 255, 0.88) !important;
    }

    .btn-source-type {
    min-width: 120px;
    margin-left: 4px;
    }

    /* Invoicing index lists — same striped/bordered table feel as cashier History */
    .invoice-index-list .history-list-table {
      table-layout: fixed;
      width: 100%;
    }

    .invoice-index-list .history-list-table th,
    .invoice-index-list .history-list-table td {
      vertical-align: middle;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }

    .invoice-index-list .hist-invoice {
      white-space: nowrap;
    }

    .invoice-index-list .hist-customer,
    .invoice-index-list .hist-vehicle,
    .invoice-index-list .hist-lastprocessed {
      word-break: break-word;
      overflow-wrap: anywhere;
    }

    .invoice-index-list .hist-tag {
      white-space: nowrap;
    }

    .invoice-index-list .hist-actions {
      text-align: center;
      white-space: nowrap;
    }

    .invoice-index-list .hist-actions-inner {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.25rem;
      min-height: 2.25rem;
    }

    .invoice-index-list .hist-svc .hist-svc-select {
      min-width: 0;
      width: 100%;
      max-width: 100%;
    }

    .inv-all-invoices-search {
      width: 100%;
      max-width: 100%;
    }

    .inv-all-invoices-search .form-control {
      width: 100%;
      max-width: 100%;
    }

    /* Modal & other forms: unchanged default for service select */
    .inv-svc-status-select {
      min-width: 7.75rem;
      max-width: 12rem;
    }

    #invoiceModal .select2-results__options {
    max-height: min(280px, 45vh) !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    scrollbar-width: thin;
    scrollbar-color: #868e96 #e9ecef;
    }

    #invoiceModal .select2-results__options::-webkit-scrollbar {
    width: 8px;
    }

    #invoiceModal .select2-results__options::-webkit-scrollbar-track {
    background: #e9ecef;
    border-radius: 4px;
    }

    #invoiceModal .select2-results__options::-webkit-scrollbar-thumb {
    background: #868e96;
    border-radius: 4px;
    }

    /* Part row: picker + Manual on one horizontal line */
    #invoiceModal .inv-inv-line-part-row {
    gap: 0.35rem !important;
    }

    #invoiceModal .inv-inv-part-dd .select2-container {
    min-width: 0;
    }

    /* Item row badges — neutral on white; invert on blue highlight */
    #invoiceModal .inv-part-badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
    border: 1px solid var(--inv-select2-border);
    background: #f8f9fa;
    color: var(--inv-select2-text);
    }

    #invoiceModal .inv-part-badge-pop {
    border-color: #dee2e6;
    background: #ffffff;
    color: var(--inv-select2-muted);
    }

    #invoiceModal .select2-results__option:last-child .inv-part-opt.border-bottom {
    border-bottom: none !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted.inv-part-highlight-active,
    #invoiceModal .select2-container--default .select2-results__option--highlighted .inv-part-code {
      color: #ffffff !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted .inv-part-title {
      color: #ffffff !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted .inv-part-badge,
    #invoiceModal .select2-container--default .select2-results__option--highlighted .inv-part-badge-stk,
    #invoiceModal .select2-container--default .select2-results__option--highlighted .inv-part-badge-pop {
      background: rgba(255, 255, 255, 0.22) !important;
      border: 1px solid rgba(255, 255, 255, 0.55) !important;
      color: #ffffff !important;
    }

    #invoiceModal .select2-container--default .select2-results__option--highlighted .badge {
      background: rgba(255, 255, 255, 0.25) !important;
      border: 1px solid rgba(255, 255, 255, 0.6) !important;
      color: #ffffff !important;
    }

    /* Search input inside dropdown */
    #invoiceModal .select2-search--dropdown .select2-search__field {
      margin: 0.35rem 0.5rem 0.65rem;
      width: calc(100% - 1rem) !important;
      padding: 0.4rem 0.65rem;
      border-radius: 0.375rem;
      border: 1px solid var(--inv-select2-border);
      background: #ffffff;
      color: var(--inv-select2-text);
    }

    #invoiceModal .select2-search--dropdown .select2-search__field:focus {
      border-color: var(--inv-select2-accent);
      outline: none;
      box-shadow: 0 0 0 0.12rem rgba(74, 144, 226, 0.2);
    }

    #invoiceModal #items-table th.inv-item-drag,
    #invoiceModal #items-table td.inv-item-drag {
    width: 2.25rem;
    min-width: 2.25rem;
    vertical-align: middle;
    text-align: center;
    }

    .inv-item-drag-handle {
    cursor: grab;
    touch-action: none;
    }

    .inv-item-drag-handle:active {
    cursor: grabbing;
    }

    #invoiceModal #items-table tbody tr.sortable-ghost {
    opacity: 0.55;
    }

    /* Reserve width for money/qty so long part names do not squeeze numeric columns */
    #invoiceModal #items-table {
    table-layout: fixed;
    width: 100%;
    }

    #invoiceModal #items-table th.inv-col-qty,
    #invoiceModal #items-table td.inv-col-qty {
    width: 4.75rem;
    }

    #invoiceModal #items-table th.inv-col-money,
    #invoiceModal #items-table td.inv-col-money {
    width: 8.75rem;
    min-width: 8.75rem;
    }

    #invoiceModal #items-table th.inv-col-linetotal,
    #invoiceModal #items-table td.inv-col-linetotal {
    width: 8.75rem;
    }

    #invoiceModal #items-table th.inv-col-actions,
    #invoiceModal #items-table td.inv-col-actions {
    width: 3rem;
    min-width: 3rem;
    text-align: center;
    vertical-align: middle;
    }

    #invoiceModal #items-table td.inv-item-cell {
    min-width: 0;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    vertical-align: top;
    }

    /* Part picker row: taller selection box for wrapped labels */
    #invoiceModal #items-table .select2-container--default .select2-selection--single {
    height: auto;
    min-height: 2rem;
    background: #ffffff;
    border-color: var(--inv-select2-border);
    }

    #invoiceModal #items-table .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--inv-select2-accent);
    }

    #invoiceModal #items-table .select2-container--default .select2-selection--single .select2-selection__rendered {
    white-space: normal !important;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.35;
    padding-right: 1.75rem !important;
    overflow: visible !important;
    text-overflow: clip !important;
    color: var(--inv-select2-text);
    }

    /* Selected-value line (stock meta) stays readable */
    #invoiceModal #items-table .select2-selection--single .inv-part-sel-meta {
      color: var(--inv-select2-muted);
    }

    #invoiceModal #items-table .select2-container--default .select2-selection--single .select2-selection__arrow {
    top: 0.65rem;
    transform: none;
    height: auto;
    }

    .inv-part-sel-lines {
    display: block;
    max-width: 100%;
    }

    .inv-part-sel-lines .inv-part-sel-meta {
    display: block;
    font-size: 0.72rem;
    color: var(--inv-select2-muted, #6c757d);
    margin-top: 0.15rem;
    font-weight: 400;
    }
  </style>

  {{-- Full width of .content (no .container): a centered .container made the CTA look "floating" vs full-width tables below --}}
  <div class="mt-4 mb-3 text-end">
    <div class="invoice-page-toolbar d-inline-flex flex-wrap align-items-center justify-content-end gap-2">
    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal"
      data-bs-target="#invoiceModal" id="btnCreateInvoice">
    <i class="bi bi-plus-lg"></i> Create Invoice
    </button>
    </div>
  </div>

  {{-- Invoice Modal --}}
  <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">

    <div class="modal-content">
      <div class="modal-header">
      <h2 class="modal-title mx-auto" id="invoiceModalLabel">{{ isset($invoice) ? 'Edit Invoice' : 'Create Invoice' }}
      </h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      @if(session('success'))
      <div class="alert alert-primary alert-dismissible fade show border-0 shadow-sm" role="alert" style="background: rgba(74, 144, 226, 0.15); color: #0f172a;">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

      @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
        @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
        </ul>
      </div>
    @endif


      <form
        action="{{ isset($invoice) ? route('cashier.invoice.update', $invoice->id) : route('cashier.invoice.store') }}"
        method="POST" id="invoiceForm" autocomplete="off">
        @csrf
        @if(isset($invoice)) @method('PUT') @endif

        {{-- Header Details --}}
        <div class="card mb-4 shadow-sm">
        <div class="card-header inv-section-header fw-semibold py-3">
          Client & Vehicle Details
        </div>
        <div class="card-body p-3">
          <div class="row g-3 mb-3">
          <div class="col-12 col-md-6 col-xl-3 client-select-wrapper">
            <label class="form-label fw-bold">Client</label>
            <select name="client_id" id="client_id" class="form-select">
            <option value="">— walk‐in or choose —</option>
            @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ old('client_id', $invoice->client_id ?? '') == $c->id ? 'selected' : '' }}>
          {{ $c->name }}
        </option>
        @endforeach
            </select>
          </div>

          <div class="col-12 col-md-6 col-xl-3 manual-client-wrapper">
            <label class="form-label fw-bold">Manual Customer Name</label>
            <input type="text" name="customer_name" id="customer_name" class="form-control"
            placeholder="Enter walk-in name" value="{{ old('customer_name', $invoice->customer_name ?? '') }}">
          </div>


          <div class="col-12 col-md-6 col-xl-3 vehicle-select-wrapper">
            <label class="form-label fw-bold">Vehicle</label>
            <select name="vehicle_id" id="vehicle_id" class="form-select">
            <option value="">— walk-in or choose —</option>
            @foreach($vehicles as $v)
        <option value="{{ $v->id }}" data-plate="{{ $v->plate_number }}" {{ old('vehicle_id', $invoice->vehicle_id ?? '') == $v->id ? 'selected' : '' }}>
          {{ $v->plate_number }}
        </option>
        @endforeach
            </select>
          </div>

          <div class="col-12 col-md-6 col-xl-3 manual-vehicle-wrapper">
            <label class="form-label fw-bold">Manual Vehicle Name</label>
            <input type="text" name="vehicle_name" id="vehicle_name" class="form-control"
            placeholder="Enter vehicle details" value="{{ old('vehicle_name', $invoice->vehicle_name ?? '') }}">
          </div>


          </div>

          <div class="row g-3 mb-3">
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Plate</label>
            <input type="text" name="plate" id="plate" class="form-control"
            value="{{ old('plate', isset($invoice->vehicle) ? $invoice->vehicle->plate_number : '') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Model</label>
            <input type="text" name="model" id="model" class="form-control"
            value="{{ old('model', isset($invoice->vehicle) ? $invoice->vehicle->model : '') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Year</label>
            <input type="text" name="year" id="year" class="form-control"
            value="{{ old('year', isset($invoice->vehicle) ? $invoice->vehicle->year : '') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Color</label>
            <input type="text" name="color" id="color" class="form-control"
            value="{{ old('color', isset($invoice->vehicle) ? $invoice->vehicle->color : '') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Odometer</label>
            <input type="text" name="odometer" id="odometer" class="form-control"
            value="{{ old('odometer', isset($invoice->vehicle) ? $invoice->vehicle->odometer : '') }}">
          </div>
          </div>

          <div class="row g-3 mb-3 align-items-end">
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Status</label>
            @php
              $currentStatus = old('status', $invoice->status ?? 'unpaid');
            @endphp
            @if($currentStatus === 'paid')
            <input type="text" class="form-control" value="Paid" readonly>
            <input type="hidden" name="status" value="paid">
            @else
            <select name="status" class="form-select">
            <option value="unpaid" @selected($currentStatus == 'unpaid')>Unpaid</option>
            <option value="cancelled" @selected($currentStatus == 'cancelled')>Cancelled
            </option>
            <option value="voided" @selected($currentStatus == 'voided')>Voided</option>
            </select>
            @endif
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Service Status</label>
            <select name="service_status" class="form-select">
            <option value="pending" @selected(old('service_status', $invoice->service_status ?? '') == 'pending')>
              Pending</option>
            <option value="in_progress" @selected(old('service_status', $invoice->service_status ?? '') == 'in_progress')>In Progress</option>
            <option value="done" @selected(old('service_status', $invoice->service_status ?? '') == 'done')>Done
            </option>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Source Type</label>
            <input type="text" class="form-control"
            value="{{ old('source_type', $invoice->source_type ?? 'invoicing') }}" readonly>
            <input type="hidden" name="source_type"
            value="{{ old('source_type', $invoice->source_type ?? 'invoicing') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Number</label>
            <input type="number" name="number" class="form-control"
            value="{{ old('number', $invoice->number ?? '') }}">
          </div>
          <div class="col-12 col-sm-6 col-md-4 col-lg-2">
            <label class="form-label fw-bold">Invoice No</label>
            <input type="text" name="invoice_no" class="form-control" placeholder="INV-2025-001"
            value="{{ old('invoice_no', $invoice->invoice_no ?? '') }}" required>
          </div>
          </div>
          <div class="row g-3 mb-1">
          <div class="col-12 col-md-8">
            <label class="form-label fw-bold">Address</label>
            <input type="text" name="address" class="form-control"
            value="{{ old('address', $invoice->address ?? '') }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold">Date</label>
            <input type="date" name="created_date" class="form-control"
            value="{{ old('created_date', isset($invoice) ? \Carbon\Carbon::parse($invoice->created_at)->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d')) }}">
          </div>
          </div>
        </div> {{-- end card-body --}}
        </div> {{-- end card --}}


        {{-- Items --}}
        <div class="card mb-4 shadow-sm">
        <div class="card-header inv-section-header fw-semibold py-3">
          Items
        </div>
        <div class="card-body p-3">
          <div class="table-responsive">
          <table class="table table-bordered mb-0 align-middle" id="items-table">
          <thead class="table-light">
            <tr>
            <th class="inv-item-drag text-center" title="Drag to reorder"><span class="visually-hidden">Reorder</span></th>
            <th class="inv-item-cell">Item</th>
            <th class="inv-col-qty">Qty</th>
            <th class="inv-col-money">Price ₱</th>
            <th class="inv-col-money">Discounted ₱</th>
            <th class="inv-col-linetotal text-end">Total ₱</th>
            <th class="inv-col-actions"><span class="visually-hidden">Remove</span></th>
            </tr>
          </thead>

          <tbody></tbody>
          <tfoot>
            <tr>
            <td colspan="7" class="text-end py-2">
              <button type="button" id="add-item" class="btn btn-sm btn-primary">+ Add Item</button>
            </td>
            </tr>
          </tfoot>
          </table>
          </div>
        </div>
        </div>


        {{-- Jobs --}}
        <div class="card mb-4 shadow-sm">
        <div class="card-header inv-section-header fw-semibold py-3">
          Jobs
        </div>
        <div class="card-body p-3">
          <table class="table table-bordered" id="jobs-table">
          <thead>
            <tr>
            <th>Description</th>
            <th>Technician</th>
            <th>Total ₱</th>
            <th></th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr>
            <td colspan="4" class="text-end">
              <button type="button" id="add-job" class="btn btn-sm btn-primary">+ Add Job</button>
            </td>
            </tr>
          </tfoot>
          </table>
        </div>
        </div>


        {{-- Totals --}}
        <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-bold mb-1">Subtotal</label>
          <input type="number" step="0.01" name="subtotal" class="form-control" readonly>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-bold mb-1">Total Discount</label>
          <input type="number" name="total_discount" class="form-control"
          value="{{ old('total_discount', $invoice->total_discount ?? 0) }}">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-bold mb-1">VAT (12%)</label>
          <input type="number" step="0.01" name="vat_amount" class="form-control">
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-bold mb-1">Grand Total</label>
          <input type="number" step="0.01" name="grand_total" class="form-control" readonly>
        </div>
        </div>

        <div class="card mb-4 shadow-sm inv-payment-card border">
        <div class="card-header inv-section-header fw-semibold py-3">Payment (Trans type + split)</div>
        <div class="card-body p-3">
          <!-- Row 1: mode + total + trans type -->
          <div class="row g-3 align-items-start">
            <div class="col-12 col-md-3">
              <label class="form-label fw-bold mb-1">Payment mode</label>
              <select id="payment_mode" class="form-select inv-payment-select border">
                <option value="cash_only">Cash only</option>
                <option value="cashless_only">Cashless only</option>
                <option value="split">Split (cash + cashless)</option>
              </select>
              <div class="form-text text-secondary" style="min-height:1.35rem"><span class="invisible">.</span></div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label fw-bold mb-1">Trans type</label>
              <select name="payment_type" id="payment_type" class="form-select inv-payment-select border">
                <option value="cash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'cash')>Cash</option>
                <option value="gcash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'gcash')>G-Cash</option>
                <option value="debit" @selected(old('payment_type', $invoice->payment_type ?? '') == 'debit')>Debit</option>
                <option value="credit" @selected(old('payment_type', $invoice->payment_type ?? '') == 'credit')>Credit</option>
                <option value="non_cash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'non_cash')>Non Cash</option>
              </select>
              <div class="form-text text-secondary" id="inv_payment_type_hint">Used as cashless rail when relevant.</div>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label fw-bold mb-1">Total amount (₱)</label>
              <input type="text" class="form-control fw-semibold" id="payment_total_display" readonly>
            </div>
          </div>

          <!-- Below: entry fields (no duplicate "total" — Row 1 Total amount is the sale total) -->
          <div id="inv-pay-dynamic" class="mt-2 pt-3 border-top border-secondary-subtle">

            <!-- Split only: how much is cash vs cashless (must sum to Total amount) -->
            <div id="inv-split-alloc-row" class="row g-3 mb-1 d-none">
              <div class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1">Cash amount (₱)</label>
                <input type="number" step="0.01" name="payment_cash_amount" id="payment_cash_amount" class="form-control"
                  value="{{ old('payment_cash_amount', isset($invoice) ? $invoice->payment_cash_amount : '') }}">
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1">Cashless amount (₱)</label>
                <input type="number" step="0.01" name="payment_non_cash_amount" id="payment_non_cash_amount" class="form-control"
                  value="{{ old('payment_non_cash_amount', isset($invoice) ? $invoice->payment_non_cash_amount : '') }}">
              </div>
              <div class="col-12">
                <p class="small text-muted mb-0">
                  For split, cash + cashless normally matches <strong>Grand Total</strong>. If the customer pays more,
                  <strong>Change</strong> fills as the overage (cash + cashless − Grand Total).
                </p>
              </div>
            </div>

            <!-- Cash only, or split after amounts: physical cash given vs change -->
            <div id="inv-row-cash-flow" class="row g-3">
              <div id="inv-cell-cash-tender" class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1" id="lbl_cash_tender">Amount given (₱)</label>
                <input type="number" step="0.01" name="cash_tender_amount" id="cash_tender_amount" class="form-control"
                  value="{{ old('cash_tender_amount', isset($invoice) ? $invoice->cash_tender_amount : '') }}">
                <div class="form-text small text-muted" id="hint_cash_tender"></div>
              </div>
              <div id="inv-cell-cash-change" class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1" id="lbl_cash_change">Change (₱)</label>
                <input type="number" step="0.01" name="cash_change_amount" id="cash_change_amount" class="form-control"
                  value="{{ old('cash_change_amount', isset($invoice) ? $invoice->cash_change_amount : '') }}">
                <div id="inv-hint-split-change" class="form-text small text-muted d-none">Cash received in hand = cash amount + change (stored for records).</div>
              </div>
            </div>

            <!-- Cashless only, or split after cash line: app/transfer paid vs variance -->
            <div id="inv-row-cashless-flow" class="row g-3 mt-2 d-none">
              <div id="inv-cell-cashless-paid" class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1">Paid (cashless) (₱)</label>
                <input type="number" step="0.01" name="cashless_tender_amount" id="cashless_tender_amount" class="form-control"
                  value="{{ old('cashless_tender_amount', isset($invoice) ? $invoice->cashless_tender_amount : '') }}">
              </div>
              <div id="inv-cell-cashless-variance" class="col-12 col-md-6">
                <label class="form-label fw-bold mb-1">Cashless variance (₱)</label>
                <input type="text" class="form-control bg-light" id="cashless_variance_display" readonly tabindex="-1" value="">
                <div class="form-text small text-muted">Paid minus cashless amount</div>
              </div>
            </div>

          </div>

          @if(!isset($invoice) || (($invoice->status ?? 'unpaid') !== 'paid'))
          <div class="border-top border-secondary-subtle mt-3 pt-3 text-center">
            <button type="button" class="btn btn-success px-4" id="btnInvoiceMarkPaid">
              Mark as paid
            </button>
            <p class="form-text small text-muted mb-0 mt-2">Opens a confirmation dialog, then submits this invoice as <strong>Paid</strong> (same as Save).</p>
          </div>
          @endif
        </div>
        </div>

        <div class="modal-footer">
        <button type="submit"
          class="btn btn-primary">{{ isset($invoice) ? 'Update Invoice' : 'Save Invoice' }}</button>
        <a href="{{ route('cashier.invoice.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
      </div>
    </div>
    </div>
  </div>

  {{-- Confirm "Mark as paid" (shared: main form + Recent Unpaid table) --}}
  <div class="modal fade" id="invoiceConfirmPaymentModal" tabindex="-1"
    aria-labelledby="invoiceConfirmPaymentModalLabel" aria-hidden="true" data-bs-backdrop="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content shadow border-0">
        <div class="modal-header inv-section-header-mini py-3 border-0 rounded-top align-items-center position-relative">
          <h5 class="modal-title fs-6 text-center fw-semibold flex-grow-1 mb-0 text-white" id="invoiceConfirmPaymentModalLabel">Confirm payment</h5>
          <button type="button" class="btn-close position-absolute end-0 me-3 mt-3" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center px-4 pt-2 pb-0 small text-muted">
          Record this invoice as paid using the totals and amounts above?
        </div>
        <div class="modal-footer border-0 justify-content-center gap-2 pt-3 pb-4 flex-nowrap">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm px-3" id="invoiceConfirmPaymentOk">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <div class="invoice-index-list container mb-5 pb-3">
  {{-- UNPAID INVOICES — history-style by date --}}
  <h3 class="mt-5 fw-bold"><i class="bi bi-exclamation-circle text-primary"></i> Recent Unpaid Invoices</h3>
  @if($filteredUnpaid->isEmpty())
    <div class="alert alert-light border shadow-sm mb-3" role="alert" style="border-color:#4a90e2!important;color:#212529;">
      No unpaid invoices in the past 48 hours.
    </div>
  @else
    @foreach($groupedUnpaid as $date => $records)
      <h4 class="mt-4">{{ $date }}</h4>
      <table class="table table-striped table-bordered align-middle history-list-table mb-3">
        <colgroup>
          <col style="width:10%;">
          <col style="width:15%;">
          <col style="width:10%;">
          <col style="width:10%;">
          <col style="width:11%;">
          <col style="width:11%;">
          <col style="width:12%;">
          <col style="width:10%;">
          <col style="width:11%;">
        </colgroup>
        <thead class="table-light">
          <tr>
            <th class="hist-invoice font-monospace">Invoice #</th>
            <th class="hist-customer">Customer</th>
            <th class="hist-vehicle">Vehicle</th>
            <th class="hist-tag">Source Type</th>
            <th class="hist-pay">Payment Type</th>
            <th class="hist-svc">Service Status</th>
            <th class="hist-lastprocessed">Last processed</th>
            <th class="hist-tag">Status</th>
            <th class="hist-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($records as $h)
            <tr>
              <td class="hist-invoice font-monospace">{{ $h->invoice_no ?? '—' }}</td>
              <td class="hist-customer">{{ $h->resolvedCustomerName() }}</td>
              <td class="hist-vehicle">{{ $h->vehicle->plate_number ?? $h->vehicle_name ?? '—' }}</td>
              <td class="hist-tag">
                <span class="badge {{ $invListBadgeClass[$h->source_type] ?? 'bg-secondary' }}">
                  {{ ucfirst(str_replace('_', ' ', $h->source_type)) }}
                </span>
              </td>
              <td class="hist-pay">{{ $h->paymentTypeDisplay() }}</td>
              <td class="hist-svc">
                <form action="{{ route('cashier.invoice.update', $h->id) }}" method="POST" class="m-0">
                  @csrf @method('PUT')
                  <input type="hidden" name="status" value="unpaid">
                  <select name="service_status" class="form-select form-select-sm inv-svc-status-select hist-svc-select"
                    onchange="this.form.submit()" data-bs-toggle="tooltip" title="Change Service Status">
                    <option value="pending" {{ $h->service_status == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $h->service_status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="done" {{ $h->service_status == 'done' ? 'selected' : '' }}>Done</option>
                  </select>
                </form>
              </td>
              <td class="hist-lastprocessed">{{ $h->lastProcessedByUser?->attributionName() ?? '—' }}</td>
              <td class="hist-tag">
                <span class="badge bg-secondary text-white">Unpaid</span>
              </td>
              <td class="hist-actions">
                <div class="hist-actions-inner">
                  <a href="{{ route('cashier.invoice.view', $h->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                  <a href="{{ route('cashier.invoice.edit', $h->id) }}?modal=1" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit Invoice">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  @endif

  {{-- PAID INVOICES — history-style by date --}}
  <h3 class="mt-5 fw-bold"><i class="bi bi-check-circle text-primary"></i> Recent Paid Invoices</h3>
  @if($filteredPaid->isEmpty())
    <div class="alert alert-light border shadow-sm mb-3" role="alert" style="border-color:#4a90e2!important;color:#212529;">
      No paid invoices in the past 48 hours.
    </div>
  @else
    @foreach($groupedPaid as $date => $records)
      <h4 class="mt-4">{{ $date }}</h4>
      <table class="table table-striped table-bordered align-middle history-list-table mb-3">
        <colgroup>
          <col style="width:10%;">
          <col style="width:15%;">
          <col style="width:10%;">
          <col style="width:10%;">
          <col style="width:11%;">
          <col style="width:11%;">
          <col style="width:12%;">
          <col style="width:10%;">
          <col style="width:11%;">
        </colgroup>
        <thead class="table-light">
          <tr>
            <th class="hist-invoice font-monospace">Invoice #</th>
            <th class="hist-customer">Customer</th>
            <th class="hist-vehicle">Vehicle</th>
            <th class="hist-tag">Source Type</th>
            <th class="hist-pay">Payment Type</th>
            <th class="hist-svc">Service Status</th>
            <th class="hist-lastprocessed">Last processed</th>
            <th class="hist-tag">Status</th>
            <th class="hist-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($records as $h)
            <tr>
              <td class="hist-invoice font-monospace">{{ $h->invoice_no ?? '—' }}</td>
              <td class="hist-customer">{{ $h->resolvedCustomerName() }}</td>
              <td class="hist-vehicle">{{ $h->vehicle->plate_number ?? $h->vehicle_name ?? '—' }}</td>
              <td class="hist-tag">
                <span class="badge {{ $invListBadgeClass[$h->source_type] ?? 'bg-secondary' }}">
                  {{ ucfirst(str_replace('_', ' ', $h->source_type)) }}
                </span>
              </td>
              <td class="hist-pay">{{ $h->paymentTypeDisplay() }}</td>
              <td class="hist-svc">{{ ucfirst(str_replace('_', ' ', $h->service_status)) }}</td>
              <td class="hist-lastprocessed">{{ $h->lastProcessedByUser?->attributionName() ?? '—' }}</td>
              <td class="hist-tag">
                <span class="badge {{ $invListStatusBadge[$h->status] ?? 'bg-secondary' }}">{{ ucfirst($h->status) }}</span>
              </td>
              <td class="hist-actions">
                <div class="hist-actions-inner">
                  <a href="{{ route('cashier.invoice.view', $h->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                  <a href="{{ route('cashier.invoice.edit', $h->id) }}?modal=1" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit Invoice">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endforeach
  @endif

  @isset($recentAll)
  {{-- ALL INVOICES (Paginated + live search) --}}
  <h3 class="mt-5 fw-bold"><i class="bi bi-collection text-secondary"></i> All Invoices</h3>
  <div class="inv-all-invoices-search mb-3 mt-3">
    <input type="text" id="live-search" class="form-control" placeholder="Search invoice, customer, plate, or last processor...">
  </div>

  <div id="live-search-results">
    @include('cashier.partials.invoice-results', ['results' => $recentAll])
  </div>

  @endisset

  </div>




  {{-- JS Assets --}}
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
  <script>
    const invoicePartsAjaxUrl = @json(route('cashier.invoice.ajax.parts'));
    window.invoicePartsPrefill = window.invoicePartsPrefill || @json($partsPrefill ?? []);

    /** Select2 dropdown row (AJAX; no full parts[] in DOM) */
    function invInvoicePartTplResult(data) {
      if (!data) return $('<span>');
      if (data.loading) return $('<span class="small text-muted">').text('Loading…');
      var id = data.id;
      if (id === '' || id === null || typeof id === 'undefined') {
        return $('<span class="small text-muted">').text(data.text || '');
      }
      if (data.disabled) {
        return $('<div class="small px-1 py-2 text-danger">').text((data.text || '') + ' (Out of stock)');
      }
      var num = (data.part_number != null && String(data.part_number).trim() !== '') ? String(data.part_number).trim() : 'N/A';
      var title = data.item_name || '';
      var stk = data.stock !== undefined && data.stock !== null ? Number(data.stock) : NaN;
      var sold = Number(data.usage_sum || 0);
      var $wrap = $('<div>', { class: 'inv-part-opt px-2 py-2 border-bottom border-light' });
      var $head = $('<div>', { class: 'inv-part-head lh-sm mb-2' });
      $head.append($('<span>', { class: 'inv-part-code fw-medium font-monospace' }).text('[' + num + ']'));
      $head.append(document.createTextNode(' '));
      $head.append($('<span>', { class: 'inv-part-title fw-semibold' }).text(title));
      $wrap.append($head);
      var badgeRow = $('<div>', { class: 'd-flex flex-wrap gap-2 align-items-center' });
      badgeRow.append($('<span>', { class: 'inv-part-badge inv-part-badge-stk' }).text(isNaN(stk) ? 'Stock —' : ('Stock ' + stk)));
      if (sold > 0) {
        badgeRow.append($('<span>', { class: 'inv-part-badge inv-part-badge-pop' }).text('Popular · Sold ×' + sold));
      }
      $wrap.append(badgeRow);
      return $wrap;
    }

    function invInvoicePartTplSelection(data) {
      if (!data || data.id === '' || data.id === null || typeof data.id === 'undefined') {
        return $('<span class="text-muted small">').text((data && data.text) ? data.text : '-- search part --');
      }
      var num = (data.part_number != null && String(data.part_number).trim() !== '') ? String(data.part_number).trim() : 'N/A';
      var title = data.item_name || '';
      if (!title && data.text) {
        var trimmed = String(data.text).replace(/^\[[^\]]+\]\s*/, '');
        title = trimmed.split(/\s*[·]\s*(?:Stk|Stock)/)[0].trim() || trimmed;
      }
      var stk = data.stock !== undefined && data.stock !== null ? Number(data.stock) : NaN;
      var sold = Number(data.usage_sum || 0);
      var meta = [];
      if (!isNaN(stk)) {
        meta.push('Stk: ' + stk);
      }
      if (sold > 0) {
        meta.push('Sold: ' + sold);
      }
      var outOf = !!data.disabled;
      var $root = $('<div>', { class: 'inv-part-sel-lines small fw-semibold' });
      $root.css({ color: outOf ? '#b02a37' : 'inherit', paddingRight: '1.75rem' });
      $root.append(document.createTextNode('[' + num + '] ' + (title || '')));
      if (meta.length) {
        $root.append($('<span>', { class: 'inv-part-sel-meta', text: meta.join(' · ') }));
      }
      return $root;
    }

    let invoiceItemsSortable = null;

    function reindexInvoiceItemRows() {
      $('#items-table tbody tr').each(function (newIdx) {
        $(this).find('[name]').each(function () {
          const n = this.getAttribute('name');
          if (!n || n.indexOf('items[') !== 0) return;
          const newName = n.replace(/^items\[\d+]/, 'items[' + newIdx + ']');
          if (newName !== n) this.setAttribute('name', newName);
        });
      });
    }

    function initInvoiceItemsSortable() {
      const el = document.querySelector('#items-table tbody');
      if (!el || typeof Sortable === 'undefined') return;
      if (invoiceItemsSortable) {
        invoiceItemsSortable.destroy();
        invoiceItemsSortable = null;
      }
      invoiceItemsSortable = Sortable.create(el, {
        handle: '.inv-item-drag-handle',
        animation: 150,
        draggable: 'tr',
        ghostClass: 'sortable-ghost',
        onEnd: function () {
          reindexInvoiceItemRows();
          recalc();
        }
      });
    }
    const technicians = @json($technicians);




    // Client and Vehicle Search
    var $invClientSel = $('#client_id');

    /** Client AJAX: remembers last typed query from each request so it survives Select2 clearing the box on pick */
    function invClientAjaxRememberTerm(term) {
      var t = String(term || '').trim();
      if (t) $invClientSel.data('clientAjaxSearchTerm', t);
    }

    /** Wait until dropdown search input exists (Select2 mounts it slightly after select2:open) */
    function invClientSearchWhenReady(doFn) {
      var t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
      function tick() {
        var $fld = invClientSearchFld();
        if ($fld.length) return doFn($fld);
        var elapsed = ((typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now()) - t0;
        if (elapsed < 400) requestAnimationFrame(tick);
      }
      requestAnimationFrame(function () { requestAnimationFrame(tick); });
    }

    // ─── CLIENT SELECT2 ───
    $invClientSel.select2({
    placeholder: '-- search client --',
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
      url: '{{ route("cashier.ajax.clients") }}',

      dataType: 'json',
      delay: 250,
      data: params => {
        invClientAjaxRememberTerm(params.term);
        return { q: params.term };
      },
      processResults: data => ({
      results: data.map(client => ({
        id: client.id,
        text: client.name,
        number: client.number,
        address: client.address,
        email: client.email
      }))
      })
    },
    dropdownParent: $('#invoiceModal')
    });

    /** Close on pick + reopen restores filtered list via stored query */
    var $invModalForClient = $('#invoiceModal');
    function invClientSearchFld() {
      var $f = $invModalForClient.find('.select2-container.select2-container--open .select2-search__field');
      if ($f.length) return $f;
      try {
        var s2 = $invClientSel.data('select2');
        if (s2 && s2.dropdown && s2.dropdown.$dropdown && s2.dropdown.$dropdown.length) {
          return s2.dropdown.$dropdown.find('.select2-search__field');
        }
      } catch (e) {}
      return $();
    }
    $invClientSel.on('select2:closing', function () {
      var $fld = invClientSearchFld();
      if (!$fld.length) return;
      var v = String($fld.val() || '').trim();
      if (v) $invClientSel.data('clientAjaxSearchTerm', v);
    }).on('select2:open', function () {
      var term = $invClientSel.data('clientAjaxSearchTerm');
      invClientSearchWhenReady(function ($fld) {
        if (term) $fld.val(term);
        $fld.trigger('input');
      });
    }).on('select2:clear', function () {
      $invClientSel.removeData('clientAjaxSearchTerm');
    });

    // Clear vehicle select when new client is selected
    $('#client_id').on('select2:select', function () {
    $('#vehicle_id').val(null).trigger('change');
    });


    // ─── On Client Selected → Autofill Number & Address ───
    $('#client_id').on('select2:select', function (e) {
    const client = e.params.data;
    $('input[name="number"]').val(client.number || '');
    $('input[name="address"]').val(client.address || '');
    });

    if ($('#live-search').length) {
    $('#live-search').on('input', function () {
    const query = $(this).val();
    $.ajax({
      url: '{{ route("cashier.invoice.liveSearch") }}',
      data: { search: query },
      success: function (data) {
      $('#live-search-results').html(data);
      }
    });
    });
    }

    // ─── VEHICLE SELECT2 ───
    $('#vehicle_id').select2({
    placeholder: '-- search vehicle --',
    allowClear: true,
    ajax: {
      url: '{{ route("cashier.ajax.vehicles") }}',
      dataType: 'json',
      delay: 250,
      data: params => ({
      q: params.term,
      client_id: $('#client_id').val()
      }),
      processResults: data => ({
      results: data.map(vehicle => ({
        id: vehicle.id,
        text: vehicle.plate_number,
        plate_number: vehicle.plate_number,
        model: vehicle.model,
        year: vehicle.year,
        color: vehicle.color,
        odometer: vehicle.odometer
      }))
      })
    },
    dropdownParent: $('#invoiceModal')
    });

    // ─── On Vehicle Selected → Autofill Details ───
    $('#vehicle_id').on('select2:select', function (e) {
    const v = e.params.data;
    $('#plate').val(v.plate_number || '');
    $('#model').val(v.model || '');
    $('#year').val(v.year || '');
    $('#color').val(v.color || '');
    $('#odometer').val(v.odometer || '');
    });


    function formatEditableAmount(value) {
      const num = Number(value);
      if (!Number.isFinite(num)) return '';
      const rounded = Math.round((num + Number.EPSILON) * 100) / 100;
      if (Math.abs(rounded % 1) < 1e-9) return String(Math.trunc(rounded));
      return rounded.toFixed(2).replace(/\.?0+$/, '');
    }

    // Item row with select2 and correct price autopopulate!
    // ─── Item row with Manual‐toggle + select2 autopopulate ───
    // ─── Item row with Manual-popup + Select2 autopopulate ───
    function addItemRow(data = null) {
    const idx = $('#items-table tbody tr').length;
    const partId = data?.part_id || '';
    const qty = data?.quantity || 1;
    const price = formatEditableAmount(data?.original_price ?? '');


    const lineTotal = formatEditableAmount((qty && price) ? (qty * price) : 0);

    const row = $(`<tr>
    <td class="inv-item-drag align-middle text-center">
      <button type="button" class="btn btn-link btn-sm inv-item-drag-handle p-0 text-secondary" title="Drag to reorder" aria-label="Drag to reorder row">
        <i class="fas fa-grip-vertical"></i>
      </button>
    </td>
    <td class="inv-item-cell">
      <div class="d-flex flex-nowrap align-items-start gap-1 inv-inv-line-part-row">
      <div class="inv-inv-part-dd flex-grow-1" style="min-width:0">
      <select name="items[${idx}][part_id]"
      class="form-select form-select-sm part-select"
      style="width:100%">
      <option value="">-- search part --</option>
      </select>
      </div>
      <button type="button" class="btn btn-outline-primary btn-sm manual-toggle flex-shrink-0">Manual</button>
      </div>
      <div class="manual-fields mt-2 d-none">
    <input type="text" name="items[${idx}][manual_part_name]" class="form-control form-control-sm mb-1" placeholder="Part Name">
    <input type="text" name="items[${idx}][manual_serial_number]" class="form-control form-control-sm mb-1" placeholder="Serial #">
    <input type="number" name="items[${idx}][manual_acquisition_price]" step="0.01" class="form-control form-control-sm mb-1" placeholder="Acquisition ₱">
    <input type="number" name="items[${idx}][manual_selling_price]" step="0.01" class="form-control form-control-sm mb-1" placeholder="Selling ₱">
    <div class="d-flex gap-2">
    <button type="button" class="btn btn-sm btn-secondary cancel-manual">Cancel</button>
    <button type="button" class="btn btn-sm btn-primary save-manual">Save</button>
    </div>
    </div>

    </td>
    <td class="inv-col-qty"><input name="items[${idx}][quantity]" type="number" class="form-control form-control-sm" value="${qty}">
      <input type="hidden" name="items[${idx}][acquisition_price]" class="acquisition-price" value="">
    </td>
    <td class="inv-col-price inv-col-money">
      <input name="items[${idx}][price]" type="number" step="0.01" class="form-control form-control-sm" value="${price}">
      <input type="hidden" name="items[${idx}][original_price]" value="${price}">
    </td>

    <td class="inv-col-discount inv-col-money"><input name="items[${idx}][discount_value]" type="number" step="0.01" class="form-control form-control-sm" value="${formatEditableAmount(data?.discount_value ?? '')}"></td>

    <td class="col-line-total inv-col-linetotal text-end"><span class="line-total-amount">${lineTotal}</span></td>
    <td class="inv-col-actions"><button type="button" class="btn btn-sm btn-outline-dark remove-btn" title="Remove row">✕</button></td>
    </tr>`);

    const $sel = row.find('.part-select').select2({
      placeholder: '-- search part --',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#invoiceModal'),
      minimumInputLength: 0,
      ajax: {
        url: invoicePartsAjaxUrl,
        dataType: 'json',
        delay: 200,
        data: function (params) {
          return { q: params.term || '', page: params.page || 1 };
        },
        processResults: function (data, params) {
          params.page = params.page || 1;
          return {
            results: data.results,
            pagination: { more: data.pagination.more }
          };
        },
        cache: true
      },
      templateResult: invInvoicePartTplResult,
      templateSelection: invInvoicePartTplSelection
    });

    /** Part picker: closes on choice; reopening restores last search query + AJAX list */
    var $invoiceModalParts = $('#invoiceModal');
    $sel.on('select2:closing', function () {
      var $fld = $invoiceModalParts.find('.select2-container.select2-container--open .select2-search__field');
      if ($fld.length) {
        $sel.data('invPartAjaxSearchTerm', String($fld.val() || '').trim());
      }
    }).on('select2:open', function () {
      var term = $sel.data('invPartAjaxSearchTerm');
      if (!term) return;
      requestAnimationFrame(function () {
        var $fld = $invoiceModalParts.find('.select2-container.select2-container--open .select2-search__field');
        if (!$fld.length) return;
        $fld.val(term);
        $fld.trigger('input');
      });
    }).on('select2:clear', function () {
      $sel.removeData('invPartAjaxSearchTerm');
    });

    if (partId) {
      var pre = window.invoicePartsPrefill && window.invoicePartsPrefill[String(partId)];
      if (pre) {
        var opt = new Option(pre.text, String(pre.id), true, true);
        $sel.append(opt).trigger('change');
        row.find('[name$="[price]"]').val(formatEditableAmount(pre.price));
        row.find('[name$="[original_price]"]').val(Number(pre.price).toFixed(2));
        row.find('[name$="[acquisition_price]"]').val(Number(pre.acquisition).toFixed(2));
      } else {
        $sel.append(new Option('Part #' + partId, String(partId), true, true)).trigger('change');
      }
    }

    // inventory selection → pricing (also stash search query; closing timing can strip --open)
    $sel.on('select2:select', e => {
      var $fldSel = $invoiceModalParts.find('.select2-container.select2-container--open .select2-search__field');
      if ($fldSel.length) {
        $sel.data('invPartAjaxSearchTerm', String($fldSel.val() || '').trim());
      }
      const price = e.params.data.price || 0;
      const acquisitionPrice = e.params.data.acquisition_price || 0;
      row.find('[name$="[price]"]').val(formatEditableAmount(price));
      row.find('[name$="[original_price]"]').val(price.toFixed(2));
      row.find('[name$="[acquisition_price]"]').val(acquisitionPrice.toFixed(2));
      row.find('[name$="[quantity]"]').val(1);
      recalc();
    })


    // qty/price inputs → keep posted original_price in sync with edited price, then recalc
    row.find('[name$="[price]"]').on('input', function () {
      const currentPrice = parseFloat($(this).val());
      row.find('[name$="[original_price]"]').val(Number.isFinite(currentPrice) ? currentPrice.toFixed(2) : '');
      recalc();
    });
    row.find('[name$="[quantity]"], [name$="[discount_value]"]').on('input', recalc);



    // remove row
    row.find('.remove-btn').on('click', () => { row.remove(); reindexInvoiceItemRows(); recalc(); });

    // Manual fields handlers
    row.find('.manual-toggle').on('click', () => {
      row.find('.manual-fields').removeClass('d-none');
      row.find('.inv-inv-line-part-row').addClass('d-none');
    });
    row.find('.cancel-manual').on('click', () => {
      row.find('.manual-fields').addClass('d-none');
      row.find('.inv-inv-line-part-row').removeClass('d-none');
    });
    row.find('.save-manual').on('click', () => {
      const curIdx = row.index();
      const partName = row.find('[name$="[manual_part_name]"]').val() || '';
      const serial = row.find('[name$="[manual_serial_number]"]').val() || '';
      const acq = parseFloat(row.find('[name$="[manual_acquisition_price]"]').val()) || 0;
      const sell = parseFloat(row.find('[name$="[manual_selling_price]"]').val()) || 0;

      row.find('[name$="[price]"]').val(formatEditableAmount(sell));
      row.find('[name$="[original_price]"]').val(sell.toFixed(2));
      row.find('[name$="[quantity]"]').val(1);
      row.find('[name$="[acquisition_price]"]').val(acq.toFixed(2));

      row.find('td.inv-item-cell').first().html(`
    <input type="text" name="items[${curIdx}][manual_part_name]" class="form-control form-control-sm mb-1" value="${String(partName).replace(/"/g, '&quot;')}" readonly>
    <input type="text" name="items[${curIdx}][manual_serial_number]" class="form-control form-control-sm mb-1" value="${String(serial).replace(/"/g, '&quot;')}" readonly>
    <input type="number" name="items[${curIdx}][manual_acquisition_price]" class="form-control form-control-sm mb-1" value="${acq}" readonly>
    <input type="number" name="items[${curIdx}][manual_selling_price]" class="form-control form-control-sm mb-1" value="${sell}" readonly>
    `);

      recalc();
    });

    if (data?.manual_part_name) {
      row.find('.manual-fields').removeClass('d-none');
      row.find('.inv-inv-line-part-row').addClass('d-none');

      row.find('[name$="[manual_part_name]"]').val(data.manual_part_name);
      row.find('[name$="[manual_serial_number]"]').val(data.manual_serial_number);
      row.find('[name$="[manual_acquisition_price]"]').val(data.manual_acquisition_price);
      row.find('[name$="[manual_selling_price]"]').val(data.manual_selling_price);

      row.find('[name$="[acquisition_price]"]').val(data.manual_acquisition_price);
      row.find('[name$="[price]"]').val(formatEditableAmount(data.manual_selling_price));

      row.find('td.inv-item-cell').first().html(`
    <input type="text" name="items[${idx}][manual_part_name]" class="form-control form-control-sm mb-1" value="${String(data.manual_part_name ?? '').replace(/"/g, '&quot;')}" readonly>
    <input type="text" name="items[${idx}][manual_serial_number]" class="form-control form-control-sm mb-1" value="${String(data.manual_serial_number ?? '').replace(/"/g, '&quot;')}" readonly>
    <input type="number" name="items[${idx}][manual_acquisition_price]" class="form-control form-control-sm mb-1" value="${data.manual_acquisition_price}" readonly>
    <input type="number" name="items[${idx}][manual_selling_price]" class="form-control form-control-sm mb-1" value="${data.manual_selling_price}" readonly>
    `);
    }

    $('#items-table tbody').append(row);
    recalc();
    }

    function toggleClientVehicleMode() {
    const manualName = $('#customer_name').val().trim();
    const manualVehicle = $('#vehicle_name').val().trim();
    const hasManualInput = manualName || manualVehicle;

    const hasDropdownSelected = $('#client_id').val() || $('#vehicle_id').val();

    if (hasManualInput) {
      $('.client-select-wrapper, .vehicle-select-wrapper').hide();
      $('.manual-client-wrapper, .manual-vehicle-wrapper').show();
      if ($('#client_id').val()) {
      $('#client_id').val(null).trigger('change');
      }
      if ($('#vehicle_id').val()) {
      $('#vehicle_id').val(null).trigger('change');
      }

    } else if (hasDropdownSelected) {
      $('.manual-client-wrapper, .manual-vehicle-wrapper').hide();
      $('.client-select-wrapper, .vehicle-select-wrapper').show();
    } else {
      // No input at all
      $('.client-select-wrapper, .vehicle-select-wrapper').show();
      $('.manual-client-wrapper, .manual-vehicle-wrapper').show();
    }
    }



    $('#customer_name').on('input', toggleClientVehicleMode);
    $('#client_id').on('change', toggleClientVehicleMode);

    $(document).ready(toggleClientVehicleMode);



    // JOB ROW HANDLING
    function addJobRow(data = null) {
    const idx = $('#jobs-table tbody tr').length;
    const desc = data && data.job_description ? data.job_description : '';
    const techId = data && data.technician_id ? data.technician_id : '';
    const total = data && data.total ? data.total : '';
    const row = $(`<tr>
      <td class="text-center align-middle"><input name="jobs[${idx}][job_description]" class="form-control form-control-sm" value="${desc}"></td>
      <td class="text-center align-middle">
      <select name="jobs[${idx}][technician_id]" class="form-select form-select-sm">
      <option value="">-- select tech --</option>
      ${technicians.map(t => `<option value="${t.id}" ${techId == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
      </select>
      </td>
      <td class="text-center align-middle"><input name="jobs[${idx}][total]" type="number" step="0.01" class="form-control form-control-sm" value="${total}"></td>
      <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-dark remove-btn" title="Remove row">✕</button></td>
    </tr>`);
    row.find('[name$="[total]"]').on('input', recalc);
    row.find('.remove-btn').on('click', function () {
      row.remove(); recalc();
    });
    $('#jobs-table tbody').append(row);
    recalc();
    }

    // TOTALS CALCULATION
    function recalc() {
    let itemsTotal = 0;
    let jobsTotal = 0;

    // Calculate items line totals
    $('#items-table tbody tr').each(function () {
      const $r = $(this);
      const qty = +$r.find('[name$="[quantity]"]').val() || 0;
      const price = +$r.find('[name$="[price]"]').val() || 0;
      const discounted = +$r.find('[name$="[discount_value]"]').val() || 0;
      const finalPrice = price - discounted;
      const lineTotal = qty * finalPrice;




      itemsTotal += lineTotal;

      $r.find('.line-total-amount').text(formatEditableAmount(lineTotal));
    });

    // Calculate jobs total
    $('#jobs-table tbody tr').each(function () {
      jobsTotal += +$(this).find('[name$="[total]"]').val() || 0;
    });

    // Calculate totals
    const subtotal = itemsTotal + jobsTotal;
    const totalDiscount = parseFloat($('[name="total_discount"]').val()) || 0;
    const netAfterDisc = subtotal - totalDiscount;
    const vatAmount = netAfterDisc * (0.12 / 1.12);

    // Set values back
    $('[name="subtotal"]').val(formatEditableAmount(subtotal));
    $('[name="vat_amount"]').val(formatEditableAmount(vatAmount));
    $('[name="grand_total"]').val(formatEditableAmount(netAfterDisc));
    syncPaymentPanel();
    }

    function updateChangeFromTender() {
      const mode = $('#payment_mode').val();
      const pc = parseFloat($('#payment_cash_amount').val()) || 0;
      const pn = parseFloat($('#payment_non_cash_amount').val()) || 0;

      if (mode === 'split') {
        const gt = parseFloat($('[name="grand_total"]').val()) || 0;
        const totalCollected = pc + pn;
        const ch = Math.max(0, totalCollected - gt);
        $('#cash_change_amount').val(totalCollected > 0 ? formatEditableAmount(ch) : '');
        $('#cash_tender_amount').val(formatEditableAmount(pc + ch));
        // #region agent log
        fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'c4fe64' },
          body: JSON.stringify({
            sessionId: 'c4fe64',
            location: 'invoice.blade.php:updateChangeFromTender',
            message: 'split change from cash+cashless-grand',
            data: {
              gt,
              pc,
              pn,
              totalCollected,
              changeCalc: ch,
              tenderComputed: $('#cash_tender_amount').val(),
            },
            timestamp: Date.now(),
            hypothesisId: 'H_split_math',
            runId: window.__invPayDbgRun || 'initial',
          }),
        }).catch(() => {});
        // #endregion agent log
      } else if (mode === 'cash_only') {
        const t = $('#cash_tender_amount').val();
        if (t !== '' && t !== null) {
          const tender = parseFloat(t);
          if (!isNaN(tender)) {
            $('#cash_change_amount').val(formatEditableAmount(Math.max(0, tender - pc)));
          }
        }
      }

      if (mode === 'cashless_only') {
        const cl = $('#cashless_tender_amount').val();
        if (cl !== '' && cl !== null) {
          const c = parseFloat(cl);
          if (!isNaN(c)) {
            $('#cashless_variance_display').val(formatEditableAmount(c - pn));
          }
        } else {
          $('#cashless_variance_display').val('');
        }
      }
    }

    /** Rebuild Trans type options by payment mode: split shows “Cash / …” pairs; others keep single-rail labels. */
    function rebuildPaymentTypeSelect() {
      var mode = $('#payment_mode').val();
      var cur = $('#payment_type').val();
      var $sel = $('#payment_type').empty();
      function add(v, label) {
        $('<option/>', { value: v, text: label }).appendTo($sel);
      }
      var splitRails = ['gcash', 'debit', 'credit', 'non_cash'];
      if (mode === 'split') {
        add('gcash', 'Cash / G-Cash');
        add('debit', 'Cash / Debit');
        add('credit', 'Cash / Credit');
        add('non_cash', 'Cash / Non cash');
        if (!cur || cur === 'cash' || splitRails.indexOf(cur) === -1) {
          cur = 'gcash';
        }
      } else if (mode === 'cashless_only') {
        add('gcash', 'G-Cash');
        add('debit', 'Debit');
        add('credit', 'Credit');
        add('non_cash', 'Non Cash');
        if (!cur || cur === 'cash') {
          cur = 'gcash';
        }
        if (['gcash', 'debit', 'credit', 'non_cash'].indexOf(cur) === -1) {
          cur = 'gcash';
        }
      } else {
        add('cash', 'Cash');
        add('gcash', 'G-Cash');
        add('debit', 'Debit');
        add('credit', 'Credit');
        add('non_cash', 'Non Cash');
        if (!cur) {
          cur = 'cash';
        }
      }
      $sel.val(cur);
      if (!$sel.val()) {
        $sel.find('option:first').prop('selected', true);
      }
      var hint = $('#inv_payment_type_hint');
      if (hint.length) {
        hint.text(mode === 'split'
          ? 'Which cashless method pairs with cash in this split.'
          : 'Used as cashless rail when relevant.');
      }
    }

    function inferPaymentMode(inv) {
      if (!inv) {
        $('#payment_mode').val('cash_only');
        rebuildPaymentTypeSelect();
        return;
      }
      const pc = inv.payment_cash_amount != null && inv.payment_cash_amount !== '' ? parseFloat(inv.payment_cash_amount) : null;
      const pn = inv.payment_non_cash_amount != null && inv.payment_non_cash_amount !== '' ? parseFloat(inv.payment_non_cash_amount) : null;
      if (pc != null && pn != null && pc > 0.005 && pn > 0.005) {
        $('#payment_mode').val('split');
      } else if (pc != null && (pn == null || pn < 0.005)) {
        $('#payment_mode').val('cash_only');
      } else {
        $('#payment_mode').val('cashless_only');
      }
      rebuildPaymentTypeSelect();
      let ptt = inv.payment_type ? String(inv.payment_type) : '';
      if ($('#payment_mode').val() === 'split' && ptt === 'cash') {
        ptt = 'gcash';
      }
      if (ptt) {
        $('#payment_type').val(ptt);
        if (!$('#payment_type').val()) {
          $('#payment_type').val($('#payment_mode').val() === 'split' ? 'gcash' : 'cash');
        }
      }

      $('#cash_tender_amount').val('');
      $('#cash_change_amount').val('');
      $('#cashless_tender_amount').val('');

      const mode = $('#payment_mode').val();
      if (mode === 'cashless_only') {
        if (inv.cashless_tender_amount != null && inv.cashless_tender_amount !== '') {
          $('#cashless_tender_amount').val(inv.cashless_tender_amount);
        } else if (inv.cash_tender_amount != null && inv.cash_tender_amount !== '') {
          $('#cashless_tender_amount').val(inv.cash_tender_amount);
        }
        return;
      }

      if (inv.cash_tender_amount != null && inv.cash_tender_amount !== '') $('#cash_tender_amount').val(inv.cash_tender_amount);
      if (inv.cash_change_amount != null && inv.cash_change_amount !== '') $('#cash_change_amount').val(inv.cash_change_amount);
      if (mode === 'split' && (inv.cash_change_amount == null || inv.cash_change_amount === '')
        && inv.cash_tender_amount != null && inv.cash_tender_amount !== '' && pc != null && !isNaN(pc)) {
        const tn = parseFloat(inv.cash_tender_amount);
        if (!isNaN(tn)) {
          $('#cash_change_amount').val(formatEditableAmount(Math.max(0, tn - pc)));
        }
      }
      if (mode === 'split') {
        $('#cashless_tender_amount').val('');
      }
    }

    /** Top row = mode + total + trans. Below: split amounts (split only), cash given+change (cash or split), cashless paid (cashless or split). */
    function togglePaymentDynamicSection() {
      const mode = $('#payment_mode').val();
      $('#inv-split-alloc-row').toggleClass('d-none', mode !== 'split');

      const showCashFlow = mode === 'cash_only' || mode === 'split';
      const showCashlessFlow = mode === 'cashless_only';

      $('#inv-row-cash-flow').toggleClass('d-none', !showCashFlow);
      $('#inv-row-cashless-flow').toggleClass('d-none', !showCashlessFlow);
      $('#inv-cell-cash-tender').toggleClass('d-none', mode === 'split');
      $('#inv-hint-split-change').toggleClass('d-none', mode !== 'split');
      $('#inv-cell-cash-change').toggleClass('col-md-6', mode !== 'split');

      if (mode === 'cash_only') {
        $('#lbl_cash_tender').text('Amount given (₱)');
        $('#lbl_cash_change').text('Change (₱)');
        $('#hint_cash_tender').text('Compared to Total amount.');
      } else if (mode === 'split') {
        $('#lbl_cash_change').text('Change (₱)');
      }

      // #region agent log
      fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'c4fe64' },
        body: JSON.stringify({
          sessionId: 'c4fe64',
          location: 'invoice.blade.php:togglePaymentDynamicSection',
          message: 'payment UI visibility',
          data: {
            mode,
            splitShown: !$('#inv-split-alloc-row').hasClass('d-none'),
            cashFlowShown: !$('#inv-row-cash-flow').hasClass('d-none'),
            cashlessFlowShown: !$('#inv-row-cashless-flow').hasClass('d-none'),
          },
          timestamp: Date.now(),
          hypothesisId: 'H_layout',
          runId: window.__invPayDbgRun || 'initial',
        }),
      }).catch(() => {});
      // #endregion agent log
    }

    function syncPaymentPanel() {
      rebuildPaymentTypeSelect();
      const gt = parseFloat($('[name="grand_total"]').val()) || 0;
      $('#payment_total_display').val(formatEditableAmount(gt));
      const mode = $('#payment_mode').val();
      if (mode === 'cash_only') {
        $('#payment_cash_amount').val(formatEditableAmount(gt));
        $('#payment_non_cash_amount').val('0.00');
        $('#payment_type').val('cash');
      } else if (mode === 'cashless_only') {
        $('#payment_cash_amount').val('0.00');
        $('#payment_non_cash_amount').val(formatEditableAmount(gt));
        var pt = $('#payment_type').val();
        if (!pt || pt === 'cash') $('#payment_type').val('gcash');
      } else if (mode === 'split') {
        $('#cashless_tender_amount').val('');
        $('#cashless_variance_display').val('');
      }
      updateChangeFromTender();
      togglePaymentDynamicSection();
    }





    /// ERROR CHECK (new—Jobs only)
    $('#invoiceForm').on('submit', function (e) {
    let hasBlankJob = false;
    $('#jobs-table tbody tr').each(function () {
      const desc = $(this).find('[name$="[job_description]"]').val();
      if (!desc) { hasBlankJob = true; }
    });
    if (hasBlankJob) {
      e.preventDefault();
      alert('Please remove extra blank rows in Jobs before submitting.');
      return false;
    }
    if ($('[name="status"]').val() === 'paid') {
      const gt = parseFloat($('[name="grand_total"]').val()) || 0;
      const pc = parseFloat($('#payment_cash_amount').val()) || 0;
      const pn = parseFloat($('#payment_non_cash_amount').val()) || 0;
      const ch = parseFloat($('#cash_change_amount').val()) || 0;
      const isSplit = pc > 0.005 && pn > 0.005;
      let ok = false;
      if (isSplit) {
        ok = Math.abs(pc + pn - gt - ch) <= 0.05;
      } else {
        ok = Math.abs(pc + pn - gt) <= 0.05;
      }
      if (!ok) {
        e.preventDefault();
        alert(isSplit
          ? 'For split (paid): Cash + cashless must equal Grand Total plus Change (overage back to customer). Use auto-filled Change or edit amounts.'
          : 'Cash amount + cashless amount must equal Grand Total when status is Paid.');
        return false;
      }
    }
    return true;
    });

    // INIT: Create and Edit Logic
    $('#add-item').on('click', () => addItemRow());
    $('#add-job').on('click', () => addJobRow());

    // If editing, populate items/jobs; if not, start blank row
    function populateForm(invoice) {
    $('#items-table tbody').empty();
    $('#jobs-table tbody').empty();

    if (invoice && invoice.items && invoice.items.length) {
      invoice.items.forEach(item => {
      addItemRow({
        part_id: item.part_id,
        quantity: item.quantity,
        original_price: item.original_price ?? 0,
        discounted_price: item.discounted_price ?? 0,
        discount_value: item.discount_value ?? 0,
        acquisition_price: item.manual_acquisition_price ?? (item.part?.acquisition_price ?? 0),
        manual_part_name: item.manual_part_name,
        manual_serial_number: item.manual_serial_number,
        manual_acquisition_price: item.manual_acquisition_price,
        manual_selling_price: item.manual_selling_price
      });



      });
    } else if (!invoice) {
      addItemRow();
    }

    if (invoice && invoice.jobs && invoice.jobs.length) {
      invoice.jobs.forEach(job => {
      addJobRow({
        job_description: job.job_description,
        technician_id: job.technician_id,
        total: job.total
      });
      });
    } else if (!invoice) {
      addJobRow();
    }

    recalc();

    if (invoice) {
      inferPaymentMode(invoice);
      if ($('#payment_mode').val() === 'split') {
        if (invoice.payment_cash_amount != null && invoice.payment_cash_amount !== '') {
          $('#payment_cash_amount').val(formatEditableAmount(invoice.payment_cash_amount));
        }
        if (invoice.payment_non_cash_amount != null && invoice.payment_non_cash_amount !== '') {
          $('#payment_non_cash_amount').val(formatEditableAmount(invoice.payment_non_cash_amount));
        }
      }
      updateChangeFromTender();
      togglePaymentDynamicSection();
    } else {
      $('#payment_mode').val('cash_only');
      syncPaymentPanel();
    }

    $('#payment_mode').data('prevPaymentMode', $('#payment_mode').val());

    initInvoiceItemsSortable();
    }

    $(function () {

    $('#invoiceModal').on('shown.bs.modal', function () {
      initInvoiceItemsSortable();
    });

    @if(!isset($invoice))
    $('#invoiceModal').on('show.bs.modal', function () {
      // do nothing — let it keep previous inputs
    });
    @endif

    @if(isset($invoice) && request('modal') == 1)
    $('#invoiceModal').modal('show');
    populateForm(@json($invoice));
    @endif

    recalc();
    $('#payment_mode').data('prevPaymentMode', $('#payment_mode').val());
    // whenever bottom discount changes, re-run recalc()
    $('[name="total_discount"]').on('input', recalc);

    $('#payment_mode').on('change', function () {
      var $m = $(this);
      var mode = $m.val();
      var prev = $m.data('prevPaymentMode');
      if (mode === 'split' && prev !== 'split' && (prev === 'cash_only' || prev === 'cashless_only')) {
        $('#payment_cash_amount').val('');
        $('#payment_non_cash_amount').val('');
        $('#cash_change_amount').val('');
        $('#cash_tender_amount').val('');
        // #region agent log
        fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'c4fe64' },
          body: JSON.stringify({
            sessionId: 'c4fe64',
            location: 'invoice.blade.php:paymentModeChange',
            message: 'cleared split allocation on enter split',
            data: {
              hypothesisId: 'H_split_nofill',
              prev: prev,
              mode: mode,
            },
            timestamp: Date.now(),
            runId: window.__invPayDbgRun || 'initial',
          }),
        }).catch(() => {});
        // #endregion agent log
      }
      $m.data('prevPaymentMode', mode);
      syncPaymentPanel();
    });
    $('#payment_cash_amount, #payment_non_cash_amount, #cash_tender_amount, #cashless_tender_amount, #cash_change_amount').on('input', updateChangeFromTender);

    /** Select-all on focus when value parses to 0 — typing replaces instead of appending after 0.00 (e.g. 0.00344). */
    function selectAllIfNumericZero(el) {
      var raw = String($(el).val()).trim();
      if (raw === '') return false;
      var n = parseFloat(raw.replace(',', '.'));
      return !isNaN(n) && Math.abs(n) < 1e-9;
    }
    $('#payment_cash_amount, #payment_non_cash_amount, #cash_tender_amount, #cashless_tender_amount, #cash_change_amount').on('focus', function () {
      var el = this;
      var sel = selectAllIfNumericZero(el);
      // #region agent log
      fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'c4fe64' },
        body: JSON.stringify({
          sessionId: 'c4fe64',
          location: 'invoice.blade.php:moneyFocus',
          message: 'payment/tender focus zero-select',
          data: {
            hypothesisId: 'H1_cursor_append',
            id: el.id || el.name,
            raw: String($(el).val()),
            selectAllApplied: sel,
          },
          timestamp: Date.now(),
          runId: typeof window.__invPayDbgRun !== 'undefined' ? window.__invPayDbgRun : 'initial',
        }),
      }).catch(() => {});
      // #endregion agent log
      if (!sel) return;
      requestAnimationFrame(function () {
        if (typeof el.select === 'function') el.select();
      });
    });
    $('[name="total_discount"]').on('focus', function () {
      var el = this;
      var sel = selectAllIfNumericZero(el);
      // #region agent log
      fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'c4fe64' },
        body: JSON.stringify({
          sessionId: 'c4fe64',
          location: 'invoice.blade.php:discountFocus',
          message: 'total_discount focus zero-select',
          data: {
            hypothesisId: 'H1_discount',
            selectAllApplied: sel,
          },
          timestamp: Date.now(),
          runId: typeof window.__invPayDbgRun !== 'undefined' ? window.__invPayDbgRun : 'initial',
        }),
      }).catch(() => {});
      // #endregion agent log
      if (!sel) return;
      requestAnimationFrame(function () {
        if (typeof el.select === 'function') el.select();
      });
    });

    @if(!isset($invoice) || (($invoice->status ?? 'unpaid') !== 'paid'))
    $('#btnInvoiceMarkPaid').on('click', function () {
      var el = document.getElementById('invoiceConfirmPaymentModal');
      if (el) {
        bootstrap.Modal.getOrCreateInstance(el).show();
      }
    });
    @endif
    $('#invoiceConfirmPaymentOk').off('click.invMarkPaid').on('click.invMarkPaid', function () {
      var modalEl = document.getElementById('invoiceConfirmPaymentModal');
      @if(!isset($invoice) || (($invoice->status ?? 'unpaid') !== 'paid'))
      var $sel = $('#invoiceForm').find('select[name="status"]');
      if (!$sel.length) {
        // #region agent log
        fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'c4fe64'},body:JSON.stringify({sessionId:'c4fe64',location:'invoice.blade.php:markPaidNoStatusSelect',message:'no select[name=status] on invoiceForm',data:{hypothesisId:'H1_no_status_select'},timestamp:Date.now()})}).catch(function(){});
        // #endregion
        var h0 = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        if (h0) h0.hide();
        return;
      }
      if ($sel.find('option[value="paid"]').length === 0) {
        $sel.append($('<option></option>', { value: 'paid', text: 'Paid' }));
      }
      $sel.val('paid');
      $('#invoiceForm').find('select[name="service_status"]').val('done');
      var clo2 = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
      if (clo2) clo2.hide();
      // #region agent log
      fetch('http://127.0.0.1:7254/ingest/923754be-f957-4771-807a-8b9e06c373ec',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'c4fe64'},body:JSON.stringify({sessionId:'c4fe64',location:'invoice.blade.php:markPaidConfirm',message:'modal status paid + requestSubmit',data:{hypothesisId:'H2_autosubmit'},timestamp:Date.now()})}).catch(function(){});
      // #endregion
      var formEl = document.getElementById('invoiceForm');
      if (formEl && typeof formEl.requestSubmit === 'function') {
        formEl.requestSubmit();
      } else if (formEl) {
        $(formEl).trigger('submit');
      }
      @endif
    });

    });

  </script>
  @if ($errors->any())
    <script>
    $(function () {
    $('#invoiceModal').modal('show');
    });
    </script>
  @endif
@endsection