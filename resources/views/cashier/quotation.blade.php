@extends('layouts.cashier')

@section('title', isset($invoice) ? 'Edit Quotation' : 'New Quotation')

@section('content')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    body {
    background: #f6f8fa;
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
    }

    .card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s;
    background: white;
    margin-bottom: 1.5rem;
    }

    .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }

    .card-header {
    background: #4a90e2;
    color: white;
    font-weight: 600;
    font-size: 1.1rem;
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem;
    padding: 1rem 1.25rem;
    }

    .form-control,
    .form-select {
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.95rem;
    border: 1px solid #ced4da;
    transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
    border-color: #4a90e2;
    box-shadow: 0 0 0 0.15rem rgba(74, 144, 226, 0.25);
    }

    .btn-primary {
    border-radius: 0.5rem;
    padding: 0.65rem 1.5rem;
    font-weight: 500;
    font-size: 0.95rem;
    background: linear-gradient(135deg, #4a90e2, #357ab8);
    border: none;
    transition: background 0.3s;
    color: white;
    }

    .btn-primary:hover {
    background: linear-gradient(135deg, #357ab8, #4a90e2);
    }

    .btn-success,
    .btn-info,
    .btn-warning,
    .btn-danger {
    border-radius: 0.4rem;
    }

    .table th,
    .table td {
    vertical-align: middle;
    font-size: 0.92rem;
    }

    .table-hover tbody tr:hover {
    background: #f0f7ff;
    cursor: pointer;
    }

    .badge {
    font-size: 0.75rem;
    padding: 0.35em 0.6em;
    }

    .select2-container .select2-selection--single {
    height: 40px;
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
    padding: 0.25rem 0.5rem;
    }

    /* Items row: part picker shows full wrapped label (not single-line clip) */
    #items-table td.item-cell-part {
    vertical-align: top !important;
    }

    #items-table .inv-part-dd-wrap .input-group {
    align-items: flex-start;
    flex-wrap: nowrap;
    }

    #items-table .inv-part-dd-wrap .select2-container {
    flex: 1 1 auto;
    min-width: 0;
    }

    #items-table .inv-part-dd-wrap .select2-container--default .select2-selection--single {
    height: auto !important;
    min-height: 38px;
    }

    #items-table .inv-part-dd-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    white-space: normal !important;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.35;
    padding-right: 1.75rem !important;
    overflow: visible !important;
    text-overflow: clip !important;
    }

    #items-table .inv-part-dd-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
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
    color: #5c6770;
    margin-top: 0.15rem;
    font-weight: 400;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px;
    right: 10px;
    }

    .form-control[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
    }

    .btn-add {
    padding: 0.6rem 1.5rem;
    font-size: 0.95rem;
    transition: all 0.3s;
    border-radius: 0.5rem;
    }

    .btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .table tbody tr {
    transition: background-color 0.3s;
    }

    /* Cap list height so the menu stays on-screen; scroll inside the list */
    .select2-results__options {
    max-height: min(280px, 45vh) !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    }

    .select2-results__option {
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: normal;
    }

    .select2-results__option:last-child .inv-part-opt.border-bottom {
    border-bottom: none !important;
    }

    /* Green/yellow part dropdown — ONLY inside Items .inv-part-dd-wrap (not client/vehicle selects) */
    .inv-part-dd-wrap .select2-dropdown {
    z-index: 1060 !important;
    width: 100% !important;
    min-width: min(100%, 28rem);
    border: 1px solid #bbf7d0;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 1rem rgba(22, 101, 52, 0.12);
    background: #ffffff;
    }

    .inv-part-dd-wrap .inv-part-badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.2rem 0.5rem;
    border-radius: 0.35rem;
    border: 1px solid #bbf7d0;
    background: #ecfdf5;
    color: #166534;
    }

    .inv-part-dd-wrap .inv-part-badge-pop {
    border-color: #fde047;
    background: #fef9c3;
    color: #713f12;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted {
    background-color: #d1fae5 !important;
    color: #14532d !important;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted .inv-part-code {
    color: #047857 !important;
    font-weight: 650;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted .inv-part-title {
    color: #064e3b !important;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted .inv-part-badge-stk {
    background: #ffffff !important;
    border: 1px solid #4ade80 !important;
    color: #14532d !important;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted .inv-part-badge-pop {
    background: #fef08a !important;
    border: 1px solid #eab308 !important;
    color: #713f12 !important;
    }

    .inv-part-dd-wrap .select2-container--default .select2-results__option--highlighted .badge {
    background: #ffffff !important;
    border: 1px solid #86efac !important;
    color: #14532d !important;
    }

    .inv-part-dd-wrap .select2-search--dropdown .select2-search__field {
    margin: 0.25rem 0.5rem 0.5rem;
    width: calc(100% - 1rem) !important;
    padding: 0.35rem 0.5rem;
    border-radius: 0.4rem;
    border: 1px solid #bbf7d0;
    background: #fff;
    }

    .select2-container {
    width: 100% !important;
    }

    .input-group {
    position: relative;
    }

    #items-table th.item-drag-col,
    #items-table td.item-drag-col {
    width: 2.25rem;
    min-width: 2.25rem;
    max-width: 2.75rem;
    padding-left: 0.35rem;
    padding-right: 0.35rem;
    vertical-align: middle;
    }

    .item-drag-handle {
    cursor: grab;
    touch-action: none;
    }

    .item-drag-handle:active {
    cursor: grabbing;
    }

    #items-table tbody tr.sortable-ghost {
    opacity: 0.55;
    }

    #items-table td.item-cell-part,
    #items-table th.item-cell-part {
    width: 300px;
    min-width: 250px;
    max-width: 350px;
    }

    /* Force select2 inside item column */
    #items-table .select2-container {
    width: 100% !important;
    }

    #items-table td.item-cell-part .inv-part-dd-wrap {
    width: 100%;
    min-width: 0;
    }

    /* Verified H1-sibling-stacking: items card had z-index auto — Select2 dropdown z-index applied only inside that context, so Jobs/Totals siblings painted over the menu. Lift Items above following cards; keep z-index < BS modal (1055). */
    #quote-items-card {
    position: relative;
    z-index: 40;
    }
    #quote-jobs-card,
    #quote-totals-card {
    position: relative;
    z-index: 1;
    }
  </style>
  <div class="container mt-4">
    <form
    action="{{ isset($invoice) ? route('cashier.quotation.update', $invoice->id) : route('cashier.quotation.store') }}"
    method="POST" id="quoteForm" autocomplete="off">
    @csrf
    @if(isset($invoice)) @method('PUT') @endif

    {{-- -------------------- Customer & Vehicle Details -------------------- --}}
    <div class="card mb-4 shadow-sm">
      <div class="card-header">Customer & Vehicle Details</div>
      <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3" id="client-dropdown-wrap">
        <label class="form-label">Client</label>
        <select name="client_id" id="client_id" class="form-select"></select>

        </div>
        <div class="col-md-3" id="manual-customer-wrap">
        <label class="form-label">Manual Customer Name</label>
        <input type="text" name="customer_name" id="customer_name" class="form-control"
          value="{{ old('customer_name', $invoice->customer_name ?? '') }}">
        </div>

        <div class="col-md-3" id="vehicle-dropdown-wrap">
        <label class="form-label">Vehicle</label>
        <select name="vehicle_id" id="vehicle_id" class="form-select">
          <option value="">— walk-in or choose —</option>
          @foreach($vehicles as $v)
        <option value="{{ $v->id }}" {{ old('vehicle_id', $invoice->vehicle_id ?? '') == $v->id ? 'selected' : '' }}>{{ $v->plate_number }}</option>
      @endforeach
        </select>
        </div>
        <div class="col-md-3" id="manual-vehicle-wrap">
        <label class="form-label">Manual Vehicle Name</label>
        <input type="text" name="vehicle_name" id="vehicle_name" class="form-control"
          value="{{ old('vehicle_name', $invoice->vehicle_name ?? '') }}">
        </div>

      </div>
      <div class="row g-3 mt-2">
        <div class="col-md-2">
        <label class="form-label">Plate</label>
        <input type="text" name="plate" id="plate" class="form-control"
          value="{{ old('plate', isset($invoice->vehicle) ? $invoice->vehicle->plate_number : '') }}">
        </div>
        <div class="col-md-2">
        <label class="form-label">Model</label>
        <input type="text" name="model" id="model" class="form-control"
          value="{{ old('model', isset($invoice->vehicle) ? $invoice->vehicle->model : '') }}">
        </div>
        <div class="col-md-2">
        <label class="form-label">Year</label>
        <input type="text" name="year" id="year" class="form-control"
          value="{{ old('year', isset($invoice->vehicle) ? $invoice->vehicle->year : '') }}">
        </div>
        <div class="col-md-2">
        <label class="form-label">Color</label>
        <input type="text" name="color" id="color" class="form-control"
          value="{{ old('color', isset($invoice->vehicle) ? $invoice->vehicle->color : '') }}">
        </div>
        <div class="col-md-2">
        <label class="form-label">Odometer</label>
        <input type="text" name="odometer" id="odometer" class="form-control"
          value="{{ old('odometer', isset($invoice->vehicle) ? $invoice->vehicle->odometer : '') }}">
        </div>
        <div class="col-md-2">
        <label class="form-label">Payment Type</label>
        <select name="payment_type" id="quote_payment_type" class="form-select" style="background:#e6ffe3">
          <option value="cash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'cash')>Cash</option>
          <option value="debit" @selected(old('payment_type', $invoice->payment_type ?? '') == 'debit')>Debit</option>
          <option value="credit" @selected(old('payment_type', $invoice->payment_type ?? '') == 'credit')>Credit
          </option>
          <option value="non_cash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'non_cash')>Non Cash
          </option>
          <option value="split" @selected(old('payment_type', $invoice->payment_type ?? '') == 'split')>Split payment</option>
          <option value="gcash" @selected(old('payment_type', $invoice->payment_type ?? '') == 'gcash')>G-Cash</option>
        </select>
        </div>
        <div class="col-md-2">
        <label class="form-label">Number</label>
        <input type="number" name="number" class="form-control" value="{{ old('number', $invoice->number ?? '') }}">
        </div>
        <div class="col-md-4">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control"
          value="{{ old('address', $invoice->address ?? '') }}">
        </div>
      </div>
      </div>
    </div>

    {{-- -------------------- Items Table -------------------- --}}
    <div class="card mb-4 shadow-sm" id="quote-items-card">
      <div class="card-header">Items</div>
      <div class="card-body p-0">
      <table class="table mb-0" id="items-table">
        <thead>
        <tr>
          <th class="item-drag-col text-center" title="Drag rows to reorder"><span class="visually-hidden">Reorder</span></th>
          <th class="item-cell-part">Item</th>
          <th>Qty</th>
          <th>Price ₱</th>
          <th>Discounted ₱</th>
          <th>Total ₱</th>
          <th></th>
        </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
        <tr>
          <td colspan="7" class="text-end p-2">
          <button type="button" id="add-item" class="btn btn-success btn-add shadow-sm">+ Add Item</button>
          </td>
        </tr>
        </tfoot>
      </table>
      </div>
    </div>

    {{-- -------------------- Jobs Table -------------------- --}}
    <div class="card mb-4 shadow-sm" id="quote-jobs-card">
      <div class="card-header">Jobs</div>
      <div class="card-body p-0">
      <table class="table mb-0" id="jobs-table">
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
          <td colspan="4" class="text-end p-2">
          <button type="button" id="add-job" class="btn btn-success btn-add shadow-sm">+ Add Job</button>
          </td>
        </tr>
        </tfoot>
      </table>
      </div>
    </div>

    {{-- -------------------- Totals -------------------- --}}
    <div class="card mb-5 shadow-sm" id="quote-totals-card">
      <div class="card-header">Totals Summary</div>
      <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
        <label class="form-label fw-bold">Subtotal</label>
        <input type="number" step="0.01" name="subtotal" class="form-control" readonly>
        </div>
        <div class="col-md-3">
        <label class="form-label fw-bold">Total Discount</label>
        <input type="number" step="0.01" name="total_discount" class="form-control"
          value="{{ old('total_discount', $invoice->total_discount ?? 0) }}">


        </div>
        <div class="col-md-3">
        <label class="form-label fw-bold">VAT (12%)</label>
        <input type="number" step="0.01" name="vat_amount" class="form-control">
        </div>
        <div class="col-md-3">
        <label class="form-label fw-bold">Grand Total</label>
        <input type="number" step="0.01" name="grand_total" class="form-control" readonly>
        </div>
        <div id="quote-split-section" class="col-12 mt-2 pt-3 border-top @if(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '') !== 'split') d-none @endif">
        <label class="form-label fw-bold text-muted">Split payment breakdown</label>
        <p class="small text-muted mb-2">Cash + cashless should match <strong>Grand Total</strong> when recording a split.</p>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Cash amount (₱)</label>
            <input type="number" step="0.01" name="payment_cash_amount"
              value="{{ old('payment_cash_amount', isset($invoice) ? $invoice->payment_cash_amount : '') }}"
              class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Cashless amount (₱)</label>
            <input type="number" step="0.01" name="payment_non_cash_amount"
              value="{{ old('payment_non_cash_amount', isset($invoice) ? $invoice->payment_non_cash_amount : '') }}"
              class="form-control">
          </div>
        </div>
        </div>
      </div>
      </div>
    </div>


    <div class="text-end">
      <button class="btn btn-primary">{{ isset($invoice) ? 'Update Quotation' : 'Save Quotation' }}</button>
    </div>
    </form>
    <br>


    {{-- ---------- Recent Quotations (Last 48 Hours) ---------- --}}
    <div class="card mb-5 shadow-sm">
    <div class="card-header">Recent Quotations (Last 48 Hours)</div>
    <div class="card-body p-0">
      @php
    $filtered = $history->whereIn('source_type', ['quotation', 'cancelled'])
      ->where('created_at', '>=', now()->subHours(48));
    @endphp

      @if($filtered->isEmpty())
      <div class="p-4 text-center text-muted">
      No quotations or cancelled records in the past 48 hours.
      </div>
    @else
      <div class="table-responsive">
      <table class="table mb-0 table-hover align-middle">
      <thead style="background: #4a90e2; color: white;">
      <tr>
        <th>Customer</th>
        <th>Vehicle</th>
        <th>Source Type</th>
        <th>Actions</th>
      </tr>
      </thead>
      <tbody>
      @foreach($filtered as $h)
      <tr>
      <td>{{ $h->client->name ?? $h->customer_name }}</td>
      <td>{{ $h->vehicle->plate_number ?? $h->vehicle_name }}</td>
      <td>
      @php
      $badgeClass = [
      'quotation' => 'bg-warning text-dark',
      'cancelled' => 'bg-danger',
      'appointment' => 'bg-info text-dark',
      'service_order' => 'bg-secondary',
      'invoicing' => 'bg-success text-white'
      ];
      @endphp
      <span class="badge {{ $badgeClass[$h->source_type] ?? 'bg-secondary' }}">
        {{ ucfirst(str_replace('_', ' ', $h->source_type)) }}
      </span>
      </td>
      <td class="d-flex gap-1">
      <a href="{{ route('cashier.quotation.view', $h->id) }}" class="btn btn-sm btn-outline-info">View</a>
      <a href="{{ route('cashier.quotation.edit', $h->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
      <form action="{{ route('cashier.quotation.update', $h->id) }}" method="POST"
        style="display:inline-flex;align-items:center;">
        @csrf @method('PUT')
        <select name="source_type" class="form-select form-select-sm btn-source-type"
        onchange="this.form.submit()">
        <option value="quotation" {{ $h->source_type === 'quotation' ? 'selected' : '' }}>Quotation</option>
        <option value="cancelled" {{ $h->source_type === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        <option value="service_order" {{ $h->source_type === 'service_order' ? 'selected' : '' }}>Service
        Order</option>
        <option value="invoicing" {{ $h->source_type === 'invoicing' ? 'selected' : '' }}>Invoicing</option>
        </select>
        <input type="hidden" name="quick_update" value="1" />
      </form>
      </td>
      </tr>
      @endforeach
      </tbody>
      </table>
      </div>
    @endif
    </div>
    </div>

  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

  <script>
    $(function () {
    window.quotationPartsPrefill = @json((object) ($partsPrefill ?? []));

    const technicians = @json($technicians);
    const partsAjaxUrl = @json(route('cashier.quotation.ajax.parts'));

    // Autofill contact
    $('[name="number"], [name="address"]').data('manual', false).on('input', function () {
      $(this).data('manual', true);
    });

    var $qClientSel = $('#client_id');
    function qClientAjaxRememberTerm(term) {
      var t = String(term || '').trim();
      if (t) $qClientSel.data('clientAjaxSearchTerm', t);
    }
    function qClientSearchFld() {
      try {
        var s2 = $qClientSel.data('select2');
        if (s2 && s2.dropdown && s2.dropdown.$dropdown && s2.dropdown.$dropdown.length) {
          var $f = s2.dropdown.$dropdown.find('.select2-search__field');
          if ($f.length) return $f;
        }
      } catch (e) {}
      return $(document.body).find('.select2-container.select2-container--open .select2-search__field').first();
    }
    function qClientSearchWhenReady(doFn) {
      var t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
      function tick() {
        var $fld = qClientSearchFld();
        if ($fld.length) return doFn($fld);
        var elapsed = ((typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now()) - t0;
        if (elapsed < 400) requestAnimationFrame(tick);
      }
      requestAnimationFrame(function () { requestAnimationFrame(tick); });
    }
    $qClientSel.select2({
      ajax: {
      url: '{{ route("cashier.quotation.ajax.clients") }}',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        qClientAjaxRememberTerm(params.term);
        return {
        q: params.term || '',
        page: params.page || 1
        };
      },
      processResults: function (data, params) {
        params.page = params.page || 1;
        return {
        results: data.results,
        pagination: {
        more: data.pagination.more
        }
        };
      },
      cache: true
      },
      minimumInputLength: 0,
      placeholder: '-- search client --',
      allowClear: true
    }).on('select2:closing', function () {
      var $fld = qClientSearchFld();
      if (!$fld.length) return;
      var v = String($fld.val() || '').trim();
      if (v) $qClientSel.data('clientAjaxSearchTerm', v);
    }).on('select2:open', function () {
      var term = $qClientSel.data('clientAjaxSearchTerm');
      qClientSearchWhenReady(function ($fld) {
        if (term) $fld.val(term);
        $fld.trigger('input');
      });
    }).on('select2:clear', function () {
      $qClientSel.removeData('clientAjaxSearchTerm');
    }).on('select2:select', function (e) {
      const data = e.params.data;
      // Fill the fields only if user didn't type anything manually
      if (!$('[name="number"]').data('manual')) {
      $('[name="number"]').val(data.phone || '');
      }
      if (!$('[name="address"]').data('manual')) {
      $('[name="address"]').val(data.address || '');
      }
      $('#vehicle_id').val(null).trigger('change');
    });

    $('#vehicle_id').select2({
      placeholder: '-- search vehicle --',
      allowClear: true,
      closeOnSelect: false,
      ajax: {
      url: '{{ route("cashier.ajax.vehicles") }}',
      dataType: 'json',
      delay: 250,
      data: params => ({
      q: params.term || '',
      client_id: $('#client_id').val() || ''
      }),
      processResults: data => ({
      results: data.map(v => ({
        id: v.id,
        text: v.plate_number,
        plate_number: v.plate_number,
        model: v.model,
        year: v.year,
        color: v.color,
        odometer: v.odometer
      }))
      })
      }
    });

    $('#vehicle_id').on('select2:select', function (e) {
      const v = e.params.data;
      $('#plate').val(v.plate_number || '');
      $('#model').val(v.model || '');
      $('#year').val(v.year || '');
      $('#color').val(v.color || '');
      $('#odometer').val(v.odometer || '');
    });

    function reindexQuotationItemRows() {
      $('#items-table tbody tr').each(function (newIdx) {
        $(this).find('[name]').each(function () {
          const n = this.getAttribute('name');
          if (!n || n.indexOf('items[') !== 0) return;
          const newName = n.replace(/^items\[\d+]/, 'items[' + newIdx + ']');
          if (newName !== n) this.setAttribute('name', newName);
        });
      });
    }

    @if(isset($invoice) && $invoice->client)
    const clientOption = new Option(@json($invoice->client->select2Label($invoice->customer_name, optional($invoice->vehicle)->plate_number)), @json((string) $invoice->client->id), true, true);
    $('#client_id').append(clientOption).trigger('change');
    @endif

      @if(isset($invoice) && $invoice->vehicle)
    const vehicleOption = new Option("{{ $invoice->vehicle->plate_number }}", "{{ $invoice->vehicle->id }}", true, true);
    $(vehicleOption).attr({
      'data-plate': "{{ $invoice->vehicle->plate_number }}",
      'data-model': "{{ $invoice->vehicle->model }}",
      'data-year': "{{ $invoice->vehicle->year }}",
      'data-color': "{{ $invoice->vehicle->color }}",
      'data-odometer': "{{ $invoice->vehicle->odometer }}"
    });
    $('#vehicle_id').append(vehicleOption).trigger('change');
    @endif


      function addItemRow(data = null) {
      const idx = $('#items-table tbody tr').length;
      const partId = data?.part_id || '';
      const qty = data?.quantity || 1;
      const orig = data?.original_price ?? '';
      const lineTotal = (orig && qty) ? (orig * qty).toFixed(2) : '0.00';

      const row = $(`
    <tr>
    <td class="item-drag-col align-middle text-center">
      <button type="button" class="btn btn-link btn-sm item-drag-handle p-0 text-secondary" title="Drag to reorder" aria-label="Drag to reorder row">
        <i class="fas fa-grip-vertical"></i>
      </button>
    </td>
    <td class="item-cell-part">
    <div class="inv-part-dd-wrap position-relative">
    <div class="input-group">
      <select name="items[${idx}][part_id]" class="form-select form-select-sm part-select" style="width:100%">
      <option value="">-- search part --</option>
      </select>
      <button type="button" class="btn btn-warning btn-sm manual-toggle">Manual</button>
    </div>
    <div class="manual-fields mt-2 d-none">
      <input type="text" name="items[${idx}][manual_part_name]" class="form-control form-control-sm mb-1" placeholder="Part Name">
      <input type="text" name="items[${idx}][manual_serial_number]" class="form-control form-control-sm mb-1" placeholder="Serial #">
      <input type="hidden" name="items[${idx}][manual_acquisition_price]" value="">
      <input type="number" name="items[${idx}][manual_selling_price]" class="form-control form-control-sm mb-1" placeholder="Selling ₱">
      <div class="d-flex gap-2">
      <button type="button" class="btn btn-sm btn-secondary cancel-manual">Cancel</button>
      <button type="button" class="btn btn-sm btn-success save-manual">Save</button>
      </div>
    </div>
    <input type="hidden" name="items[${idx}][acquisition_price]" value="">
    </div>
    </td>
    <td><input name="items[${idx}][quantity]" type="number" class="form-control form-control-sm" value="${qty}"></td>
    <td><input name="items[${idx}][original_price]" type="number" step="0.01" class="form-control form-control-sm" value="${orig}"></td>
    <td><input name="items[${idx}][discount_value]" type="number" step="0.01" class="form-control form-control-sm" value="${data?.discount_value || ''}"></td>
    <td class="col-line-total text-end"><span class="line-total-amount">${lineTotal}</span><input type="hidden" name="items[${idx}][discounted_price]" value="0.00"></td>
    <td><button type="button" class="btn btn-sm btn-danger remove-btn">✕</button></td>
    </tr>`);

      row.find('[name$="[quantity]"], [name$="[original_price]"], [name$="[discount_value]"]').on('input', recalc);


      row.find('.manual-toggle').on('click', function () {
        row.find('.manual-fields').removeClass('d-none');
        row.find('.input-group').addClass('d-none');
      });

      const $partSelect = row.find('.part-select').select2({
        placeholder: '-- search part --',
        allowClear: true,
        width: '100%',
        dropdownParent: row.find('.inv-part-dd-wrap'),
        minimumInputLength: 0,
        ajax: {
          url: partsAjaxUrl,
          dataType: 'json',
          delay: 250,
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
        templateResult: function (data) {
          if (!data) return $('<span>');
          if (data.loading) {
            return $('<span class="small text-muted">').text('Loading…');
          }
          const id = data.id;
          if (id === '' || id === null || typeof id === 'undefined') {
            return $('<span class="small text-muted">').text(data.text || '');
          }
          if (data.disabled) {
            return $('<div class="small px-1 py-2 text-danger">').text((data.text || '') + ' (Out of stock)');
          }
          const num = (data.part_number != null && String(data.part_number).trim() !== '')
            ? String(data.part_number).trim() : 'N/A';
          const title = data.item_name || data.text || '';
          const stk = data.stock !== undefined && data.stock !== null ? Number(data.stock) : NaN;
          const sold = Number(data.usage_sum || 0);
          const $wrap = $('<div>', { class: 'inv-part-opt px-2 py-2 border-bottom border-light' });
          const $head = $('<div>', { class: 'inv-part-head lh-sm mb-2' });
          $head.append($('<span>', { class: 'inv-part-code fw-medium font-monospace' }).text('[' + num + ']'));
          $head.append(document.createTextNode(' '));
          $head.append($('<span>', { class: 'inv-part-title fw-semibold' }).text(title));
          $wrap.append($head);
          const badgeRow = $('<div>', { class: 'd-flex flex-wrap gap-2 align-items-center' });
          badgeRow.append($('<span>', { class: 'inv-part-badge inv-part-badge-stk' }).text(isNaN(stk) ? 'Stock —' : ('Stock ' + stk)));
          if (sold > 0) {
            badgeRow.append($('<span>', { class: 'inv-part-badge inv-part-badge-pop' }).text('Popular · Sold ×' + sold));
          }
          $wrap.append(badgeRow);
          return $wrap;
        },
        templateSelection: function (data) {
          if (!data || !data.id) {
            return $('<span>').text(data && data.text ? data.text : '-- search part --');
          }
          var num = (data.part_number != null && String(data.part_number).trim() !== '')
            ? String(data.part_number).trim() : 'N/A';
          var title = data.item_name;
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
          $root.css({ color: outOf ? '#b02a37' : 'inherit' });
          $root.append(document.createTextNode('[' + num + '] ' + (title || '')));
          if (meta.length) {
            $root.append($('<span>', {
              class: 'inv-part-sel-meta',
              text: meta.join(' · ')
            }));
          }
          return $root;
        }
      })
        .on('select2:closing', function () {
          var $fld = row.find('.inv-part-dd-wrap').find('.select2-container.select2-container--open .select2-search__field');
          if ($fld.length) {
            $partSelect.data('invPartAjaxSearchTerm', String($fld.val() || '').trim());
          }
        })
        .on('select2:open', function () {
          var term = $partSelect.data('invPartAjaxSearchTerm');
          if (!term) return;
          requestAnimationFrame(function () {
            var $fld = row.find('.inv-part-dd-wrap').find('.select2-container.select2-container--open .select2-search__field');
            if (!$fld.length) return;
            $fld.val(term);
            $fld.trigger('input');
          });
        })
        .on('select2:select', e => {
          var $fldSel = row.find('.inv-part-dd-wrap').find('.select2-container.select2-container--open .select2-search__field');
          if ($fldSel.length) {
            $partSelect.data('invPartAjaxSearchTerm', String($fldSel.val() || '').trim());
          }
          const d = e.params.data;
          row.find('[name$="[original_price]"]').val(Number(d.price || 0).toFixed(2));
          row.find('[name$="[acquisition_price]"]').val(Number(d.acquisition || 0).toFixed(2));
          row.find('[name$="[quantity]"]').val(1);
          recalc();
        })
        .on('select2:clear', () => {
          $partSelect.removeData('invPartAjaxSearchTerm');
          row.find('[name$="[original_price]"]').val('');
          row.find('[name$="[acquisition_price]"]').val('');
          recalc();
        });

      if (partId) {
        const pre = window.quotationPartsPrefill && window.quotationPartsPrefill[String(partId)];
        if (pre) {
          const opt = new Option(pre.text, String(pre.id), true, true);
          $partSelect.append(opt);
        } else {
          $partSelect.append(new Option('Part #' + partId, String(partId), true, true));
        }
        $partSelect.trigger('change');
        if (pre) {
          row.find('[name$="[original_price]"]').val(Number(pre.price).toFixed(2));
          row.find('[name$="[acquisition_price]"]').val(Number(pre.acquisition).toFixed(2));
        }
      }




      row.find('[name$="[quantity]"], [name$="[original_price]"]').on('input', recalc);
      row.find('.remove-btn').on('click', () => { row.remove(); reindexQuotationItemRows(); recalc(); });
      if (data?.manual_part_name) {
        row.find('.manual-fields').removeClass('d-none');
        row.find('.input-group').addClass('d-none');
        row.find('[name$="[manual_part_name]"]').val(data.manual_part_name);
        row.find('[name$="[manual_serial_number]"]').val(data.manual_serial_number);
        row.find('[name$="[manual_acquisition_price]"]').val(data.manual_acquisition_price);
        row.find('[name$="[manual_selling_price]"]').val(data.manual_selling_price);
        row.find('[name$="[acquisition_price]"]').val(data.manual_acquisition_price);
        row.find('[name$="[original_price]"]').val(data.manual_selling_price);
      }



      row.find('.cancel-manual').on('click', () => { row.find('.manual-fields').addClass('d-none'); row.find('.input-group').removeClass('d-none'); });
      row.find('.save-manual').on('click', () => {
        const curIdx = row.index();
        const partName = row.find('[name$="[manual_part_name]"]').val() || '';
        const serial = row.find('[name$="[manual_serial_number]"]').val() || '';
        const sell = parseFloat(row.find('[name$="[manual_selling_price]"]').val()) || 0;
        const acq = parseFloat(row.find('[name$="[manual_acquisition_price]"]').val()) || 0;

        row.find('[name$="[original_price]"]').val(sell.toFixed(2));
        row.find('[name$="[quantity]"]').val(1);
        row.find('[name$="[acquisition_price]"]').val(acq.toFixed(2));

        row.find('td.item-cell-part').first().html(`
    <input type="text" name="items[${curIdx}][manual_part_name]" class="form-control form-control-sm mb-1" value="${String(partName).replace(/"/g, '&quot;')}" placeholder="Part Name" readonly>
    <input type="text" name="items[${curIdx}][manual_serial_number]" class="form-control form-control-sm mb-1" value="${String(serial).replace(/"/g, '&quot;')}" placeholder="Serial #" readonly>
    <input type="hidden" name="items[${curIdx}][manual_acquisition_price]" value="${acq}">
    <input type="hidden" name="items[${curIdx}][acquisition_price]" value="${acq}">
    <input type="number" name="items[${curIdx}][manual_selling_price]" class="form-control form-control-sm mb-1" value="${sell}" placeholder="Selling ₱" readonly>
    `);

        recalc();
      });


      $('#items-table tbody').append(row);
      recalc();
      }

    function addJobRow(data = null) {
      const idx = $('#jobs-table tbody tr').length;
      const row = $(`
    <tr>
    <td><input name="jobs[${idx}][job_description]" class="form-control form-control-sm" value="${data?.job_description || ''}"></td>
    <td><select name="jobs[${idx}][technician_id]" class="form-select form-select-sm">
    <option value="">-- select tech --</option>
    ${technicians.map(t => `<option value="${t.id}" ${(data?.technician_id == t.id ? 'selected' : '')}>${t.name}</option>`).join('')}
    </select></td>
    <td><input name="jobs[${idx}][total]" type="number" step="0.01" class="form-control form-control-sm" value="${data?.total || ''}"></td>
    <td><button type="button" class="btn btn-sm btn-danger remove-btn">✕</button></td>
    </tr>`);
      row.find('[name$="[total]"]').on('input', recalc);
      row.find('.remove-btn').on('click', () => { row.remove(); recalc(); });
      $('#jobs-table tbody').append(row);
      recalc();
    }

    function recalc() {
      let itemsTotal = 0, jobsTotal = 0;
      $('#items-table tbody tr').each(function () {
      const q = +$(this).find('[name$="[quantity]"]').val() || 0;
      const op = +$(this).find('[name$="[original_price]"]').val() || 0;
      const dv = +$(this).find('[name$="[discount_value]"]').val() || 0;
      const p = op - dv;
      const t = q * p;
      itemsTotal += t;
      $(this).find('[name$="[discounted_price]"]').val(t.toFixed(2)); // set discounted_price = line_total
      $(this).find('.line-total-amount').text(t.toFixed(2));



      });
      $('#jobs-table tbody tr').each(function () {
      jobsTotal += +$(this).find('[name$="[total]"]').val() || 0;
      });
      const subtotal = itemsTotal + jobsTotal;
      const discount = +$('[name="total_discount"]').val() || 0;
      const grand = subtotal - discount;
      const vat = grand * (0.12 / 1.12);
      $('[name=subtotal]').val(subtotal.toFixed(2));
      $('[name=vat_amount]').val(vat.toFixed(2));
      $('[name=grand_total]').val(grand.toFixed(2));
    }



    $('#add-item').on('click', () => addItemRow());
    $('#add-job').on('click', () => addJobRow());
    $('[name="total_discount"]').on('input', recalc);

    function toggleQuoteSplitPayment() {
      var v = $('select[name="payment_type"]').val();
      $('#quote-split-section').toggleClass('d-none', v !== 'split');
    }
    $('select[name="payment_type"]').on('change', toggleQuoteSplitPayment);
    toggleQuoteSplitPayment();

    $('#quoteForm').on('submit', function (e) {
      let hasBlankJob = false;
      $('#jobs-table tbody tr').each(function () {
      if (!$(this).find('[name$="[job_description]"]').val()) hasBlankJob = true;
      });
      if (hasBlankJob) {
      e.preventDefault();
      alert('Please remove extra blank rows in Jobs before submitting.');
      }
    });

    @if(isset($invoice) && optional($invoice->items)->count())
      @foreach($invoice->items as $item)
      addItemRow(@json($item));
      @endforeach
    @elseif(!isset($invoice))
    addItemRow();
    @endif

    @if(isset($invoice) && optional($invoice->jobs)->count())
      @foreach($invoice->jobs as $job)
      addJobRow(@json($job));
      @endforeach
    @endif

    const itemsTbody = document.querySelector('#items-table tbody');
    if (itemsTbody && typeof Sortable !== 'undefined') {
      Sortable.create(itemsTbody, {
        handle: '.item-drag-handle',
        animation: 150,
        draggable: 'tr',
        ghostClass: 'sortable-ghost',
        onEnd: function () {
          reindexQuotationItemRows();
          recalc();
        }
      });
    }

    recalc();
    });


    function toggleMutualFields() {
    const hasManualCustomer = $('#customer_name').val().trim().length > 0;
    const hasManualVehicle = $('#vehicle_name').val().trim().length > 0;
    const hasManualInput = hasManualCustomer || hasManualVehicle;

    const hasDropdownSelected = $('#client_id').val() || $('#vehicle_id').val();

    if (hasManualInput) {
      // Hide both dropdowns
      $('#client-dropdown-wrap').hide();
      $('#vehicle-dropdown-wrap').hide();
      // Show manual inputs
      $('#manual-customer-wrap').show();
      $('#manual-vehicle-wrap').show();
    } else if (hasDropdownSelected) {
      // Hide manual inputs
      $('#manual-customer-wrap').hide();
      $('#manual-vehicle-wrap').hide();
      // Show dropdowns
      $('#client-dropdown-wrap').show();
      $('#vehicle-dropdown-wrap').show();
    } else {
      // Show all if nothing filled
      $('#client-dropdown-wrap').show();
      $('#vehicle-dropdown-wrap').show();
      $('#manual-customer-wrap').show();
      $('#manual-vehicle-wrap').show();
    }
    }

    $('#client_id, #vehicle_id').on('change', toggleMutualFields);
    $('#customer_name, #vehicle_name').on('input', toggleMutualFields);

    $(toggleMutualFields); // initial call on page load

  </script>


  @if(session('success'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1090">
      <div id="quotationSavedToast"
        class="toast align-items-center text-white bg-success border-0 shadow"
        role="alert" aria-live="polite" aria-atomic="true"
        data-bs-delay="4000" data-bs-autohide="true">
        <div class="d-flex">
          <div class="toast-body fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
            <span>Saved</span>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var el = document.getElementById('quotationSavedToast');
      if (!el || typeof bootstrap === 'undefined') return;
      var t = bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 });
      t.show();
    });
    </script>
  @endif


@endsection