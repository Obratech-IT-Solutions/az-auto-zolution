@extends('layouts.cashier')

@section('title', isset($invoice) ? 'Edit Service Order' : 'New Service Order')

@section('content')
@include('cashier.partials.cashier-flash-toast')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: #f6f8fa;
    font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
}
.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    background: white;
    margin-bottom: 1.5rem;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
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
.form-control, .form-select {
    border-radius: 0.5rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.95rem;
}
.btn-primary {
    border-radius: 0.5rem;
    padding: 0.65rem 1.5rem;
    font-size: 0.95rem;
    background: linear-gradient(135deg, #4a90e2, #357ab8);
    color: white;
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #357ab8, #4a90e2);
}

.select2-container {
    width: 100% !important;
}
</style>

<div class="container mt-4">
  <form action="{{ isset($invoice) ? route('cashier.serviceorder.update', $invoice->id) : route('cashier.serviceorder.store') }}"
        method="POST" autocomplete="off">
    @csrf
    @if(isset($invoice)) @method('PUT') @endif

    <input type="hidden" name="subtotal" value="0">
<input type="hidden" name="total_discount" value="0">
<input type="hidden" name="vat_amount" value="0">
<input type="hidden" name="grand_total" value="0">


    {{-- Customer & Vehicle --}}
    <div class="card mb-4 shadow-sm">
      <div class="card-header">Customer & Vehicle Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Client</label>
            <select name="client_id" id="client_id" class="form-select"></select>


          </div>
          <div class="col-md-3">
            <label class="form-label">Manual Customer Name</label>
            <input type="text" name="customer_name" class="form-control"
                   value="{{ old('customer_name', isset($invoice) ? ($invoice->customer_name ?? '') : '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Vehicle</label>
            <select name="vehicle_id" id="vehicle_id" class="form-select">
              <option value="">— walk-in or choose —</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Manual Vehicle Name</label>
            <input type="text" name="vehicle_name" class="form-control"
                   value="{{ old('vehicle_name', isset($invoice) ? ($invoice->vehicle_name ?? '') : '') }}">
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
            <select name="payment_type" id="so_payment_type" class="form-select" style="background:#e6ffe3">
              <option value="cash" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='cash')>Cash</option>
              <option value="debit" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='debit')>Debit</option>
              <option value="credit" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='credit')>Credit</option>
              <option value="non_cash" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='non_cash')>Non Cash</option>
              <option value="gcash" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='gcash')>G-Cash</option>
              <option value="split" @selected(old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '')=='split')>Split payment</option>
            </select>
          </div>
        </div>
        <div id="so-split-section" class="row g-3 mt-2 {{ old('payment_type', isset($invoice) ? ($invoice->payment_type ?? '') : '') === 'split' ? '' : 'd-none' }}">
          <div class="col-12">
            <label class="form-label fw-semibold text-muted">Split payment breakdown</label>
            <p class="small text-muted mb-2">Planned cash vs cashless (optional when total is not yet final).</p>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cash amount (₱)</label>
            <input type="number" step="0.01" name="payment_cash_amount" class="form-control"
              value="{{ old('payment_cash_amount', isset($invoice) ? $invoice->payment_cash_amount : '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Cashless amount (₱)</label>
            <input type="number" step="0.01" name="payment_non_cash_amount" class="form-control"
              value="{{ old('payment_non_cash_amount', isset($invoice) ? $invoice->payment_non_cash_amount : '') }}">
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-2">
            <label class="form-label fw-bold">Number</label>
            <input type="number" name="number" class="form-control"
                   value="{{ old('number', isset($invoice) ? $invoice->number : '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Address</label>
            <input type="text" name="address" class="form-control"
                   value="{{ old('address', isset($invoice) ? $invoice->address : '') }}">
          </div>
        </div>
      </div>
    </div>

    <div class="text-end">
      <button class="btn btn-primary">{{ isset($invoice) ? 'Update Service Order' : 'Save Service Order' }}</button>
    </div>
  </form>

  {{-- Recent Service Orders --}}
  <div class="card mt-5 shadow-sm">
    <div class="card-header">Recent Service Orders (Last 48 Hours)</div>
    <div class="card-body p-0">
      @if($history->isEmpty())
        <div class="p-4 text-center text-muted">No service orders in the past 48 hours.</div>
      @else
        <table class="table mb-0 table-hover align-middle">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Vehicle</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($history as $h)
              <tr>
                <td>{{ $h->customer_display }}</td>
<td>{{ $h->vehicle_display }}</td>

                <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ', $h->source_type)) }}</span></td>
                <td class="d-flex gap-2">
  <button type="button" class="btn btn-sm btn-outline-danger service-order-delete-btn" title="Delete"
    data-bs-toggle="modal" data-bs-target="#serviceOrderDeleteModal"
    data-item-label="{{ $h->customer_display }} / {{ $h->vehicle_display }}"
    data-delete-action="{{ route('cashier.serviceorder.destroy', $h->id) }}">
    <i class="bi bi-trash"></i>
  </button>

  <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"
    onclick="openEditModal(
      {{ $h->id }},
      '{{ e($h->customer_name ?? $h->client->name) }}',
      '{{ e($h->vehicle_name ?? $h->vehicle->plate_number) }}',
      '{{ e($h->number ?? '') }}',
      '{{ e($h->address ?? $h->client->address ?? '') }}',
      '{{ e($h->plate ?? $h->vehicle->plate_number ?? '') }}',
      '{{ e($h->model ?? $h->vehicle->model ?? '') }}',
      '{{ e($h->year ?? $h->vehicle->year ?? '') }}',
      '{{ e($h->color ?? $h->vehicle->color ?? '') }}',
      '{{ e($h->odometer ?? $h->vehicle->odometer ?? '') }}',
      '{{ e($h->payment_type ?? '') }}'
    )">
    <i class="bi bi-pencil-square"></i>
  </button>

  <form method="POST" action="{{ route('cashier.serviceorder.update', $h->id) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="quick_update" value="1">
    <select name="source_type" class="form-select form-select-sm" style="width: auto;"
 onchange="quickUpdateStatus(this, {{ $h->id }})">

      <option value="service_order" {{ $h->source_type == 'service_order' ? 'selected' : '' }}>Service Order</option>
      <option value="invoicing" {{ $h->source_type == 'invoicing' ? 'selected' : '' }}>Invoicing</option>
    </select>
  </form>
</td>



              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
</div>

<form id="serviceOrderDeleteForm" method="POST" class="d-none" action="">
  @csrf
  @method('DELETE')
</form>

<div class="modal fade" id="serviceOrderDeleteModal" tabindex="-1" aria-labelledby="serviceOrderDeleteModalTitle"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger overflow-hidden shadow">
      <div class="modal-header bg-danger text-white border-0 rounded-0">
        <h5 class="modal-title d-flex align-items-center gap-2" id="serviceOrderDeleteModalTitle">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span>Delete service order?</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1 text-body">This will permanently remove this service order from the list.</p>
        <p class="mb-0 small text-muted" id="serviceOrderDeleteModalMsg">This cannot be undone.</p>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="serviceOrderDeleteConfirmBtn">
          <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="editForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Service Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
  <input type="hidden" name="id" id="edit-id">
  <div class="mb-3">
    <label class="form-label">Customer</label>
    <input type="text" class="form-control" name="customer_name" id="edit-customer">
  </div>
  <div class="mb-3">
    <label class="form-label">Vehicle</label>
    <input type="text" class="form-control" name="vehicle_name" id="edit-vehicle">
  </div>
  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label">Plate</label>
      <input type="text" class="form-control" name="plate" id="edit-plate">
    </div>
    <div class="col-md-4">
      <label class="form-label">Model</label>
      <input type="text" class="form-control" name="model" id="edit-model">
    </div>
    <div class="col-md-4">
      <label class="form-label">Year</label>
      <input type="text" class="form-control" name="year" id="edit-year">
    </div>
  </div>
  <div class="row g-2 mt-2">
    <div class="col-md-4">
      <label class="form-label">Color</label>
      <input type="text" class="form-control" name="color" id="edit-color">
    </div>
    <div class="col-md-4">
      <label class="form-label">Odometer</label>
      <input type="text" class="form-control" name="odometer" id="edit-odometer">
    </div>
    <div class="col-md-4">
      <label class="form-label">Payment Type</label>
      <select class="form-select" name="payment_type" id="edit-payment">
        <option value="">— Select —</option>
        <option value="cash">Cash</option>
        <option value="debit">Debit</option>
        <option value="credit">Credit</option>
        <option value="non_cash">Non Cash</option>
        <option value="gcash">G-Cash</option>
        <option value="split">Split payment</option>
      </select>
    </div>
  </div>
  <div class="row g-2 mt-2">
    <div class="col-md-6">
      <label class="form-label">Number</label>
      <input type="number" class="form-control" name="number" id="edit-number">
    </div>
    <div class="col-md-6">
      <label class="form-label">Address</label>
      <input type="text" class="form-control" name="address" id="edit-address">
    </div>
  </div>
</div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var modalEl = document.getElementById('serviceOrderDeleteModal');
  var formEl = document.getElementById('serviceOrderDeleteForm');
  var msgEl = document.getElementById('serviceOrderDeleteModalMsg');
  if (modalEl && formEl) {
    modalEl.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (!btn) return;
      formEl.setAttribute('action', btn.getAttribute('data-delete-action') || '');
      if (msgEl) {
        var label = btn.getAttribute('data-item-label') || 'this service order';
        msgEl.textContent = label + ' will be deleted. This cannot be undone.';
      }
    });
    var confirmBtn = document.getElementById('serviceOrderDeleteConfirmBtn');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        if (!formEl.getAttribute('action')) return;
        var inst = typeof bootstrap !== 'undefined' && bootstrap.Modal
          ? bootstrap.Modal.getInstance(modalEl) : null;
        if (inst) inst.hide();
        formEl.submit();
      });
    }
  }
})();
function openEditModal(id, customer, vehicle, number, address, plate, model, year, color, odometer, payment) {
    $('#edit-id').val(id);
    $('#edit-customer').val(customer);
    $('#edit-vehicle').val(vehicle);
    $('#edit-number').val(number);
    $('#edit-address').val(address);
    $('#edit-plate').val(plate);
    $('#edit-model').val(model);
    $('#edit-year').val(year);
    $('#edit-color').val(color);
    $('#edit-odometer').val(odometer);
    $('#edit-payment').val(payment);

    let formAction = "{{ url('/cashier/serviceorder') }}/" + id;
    $('#editForm').attr('action', formAction);

    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

</script>

<script>
$(document).ready(function() {
    // Initialize Select2 for Client and Vehicle dropdowns
    var $soClientSel = $('#client_id');
    function soClientSearchFld() {
        try {
            var s2 = $soClientSel.data('select2');
            if (s2 && s2.dropdown && s2.dropdown.$dropdown && s2.dropdown.$dropdown.length) {
                var $f = s2.dropdown.$dropdown.find('.select2-search__field');
                if ($f.length) return $f;
            }
        } catch (e) {}
        return $(document.body).find('.select2-container.select2-container--open .select2-search__field').first();
    }

    function soClientSearchWhenReady(doFn) {
        var t0 = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
        function tick() {
            var $fld = soClientSearchFld();
            if ($fld.length) return doFn($fld);
            var elapsed = ((typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now()) - t0;
            if (elapsed < 400) requestAnimationFrame(tick);
        }
        requestAnimationFrame(function () { requestAnimationFrame(tick); });
    }

    $soClientSel.select2({
    ajax: {
        url: '{{ route("cashier.serviceorder.ajax.clients") }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            var t = String(params.term || '').trim();
            if (t) $soClientSel.data('clientAjaxSearchTerm', t);
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
    templateResult: function(data) {
        if (data.loading) return data.text;
        var parts = [];
        if (data.number !== undefined && data.number !== null && String(data.number).trim() !== '') {
            parts.push(String(data.number));
        }
        if (data.address && String(data.address).trim() !== '') {
            parts.push(String(data.address));
        }
        if (data.plate && String(data.plate).trim() !== '') {
            parts.push(String(data.plate));
        }
        var sub = parts.join(' · ');
        var $row = $('<span>');
        $row.append(document.createTextNode(data.text || ''));
        if (sub) {
            $row.append(document.createTextNode(' '));
            $row.append($('<small class="text-muted">').text(sub));
        }
        return $row;
    },
    templateSelection: function (data) {
        if (!data.id) return data.text;
        setTimeout(function () {
            var num = data.number;
            $('input[name="number"]').val(num !== undefined && num !== null ? String(num) : '');
            $('input[name="address"]').val(data.address || '');
        }, 100);
        var plate = data.plate && String(data.plate).trim() !== '' ? ' — ' + data.plate : '';
        return (data.text || '') + plate;
    },
    placeholder: '— walk‐in or choose —',
    minimumInputLength: 0,
    allowClear: true
});

    /** Client: close on pick; reopen restores filter; open always loads list (minInput 0) */
    $soClientSel.on('select2:closing', function () {
        var $fld = soClientSearchFld();
        if (!$fld.length) return;
        var v = String($fld.val() || '').trim();
        if (v) $soClientSel.data('clientAjaxSearchTerm', v);
    }).on('select2:open', function () {
        var term = $soClientSel.data('clientAjaxSearchTerm');
        soClientSearchWhenReady(function ($fld) {
            if (term) $fld.val(term);
            $fld.trigger('input');
        });
    }).on('select2:clear', function () {
        $soClientSel.removeData('clientAjaxSearchTerm');
    });


    $('#vehicle_id').select2({
        placeholder: '— walk-in or choose —',
        allowClear: true,
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

    $('#client_id').on('change', function() {
        $('#vehicle_id').val(null).trigger('change');
    });

    $('#vehicle_id').on('select2:select', function(e) {
        const v = e.params.data;
        $('#plate').val(v.plate_number || '');
        $('#model').val(v.model || '');
        $('#year').val(v.year || '');
        $('#color').val(v.color || '');
        $('#odometer').val(v.odometer || '');
    });

    function toggleSoSplitPayment() {
        $('#so-split-section').toggleClass('d-none', $('#so_payment_type').val() !== 'split');
    }
    $('#so_payment_type').on('change', toggleSoSplitPayment);
    toggleSoSplitPayment();

    @if(isset($invoice) && $invoice->client)
    const soClientOpt = new Option(@json($invoice->client->select2Label($invoice->customer_name, optional($invoice->vehicle)->plate_number)), @json((string) $invoice->client->id), true, true);
    $('#client_id').append(soClientOpt).trigger('change');
    @endif
    @if(isset($invoice) && $invoice->vehicle)
    const soVehOpt = new Option("{{ $invoice->vehicle->plate_number }}", "{{ $invoice->vehicle->id }}", true, true);
    $(soVehOpt).attr({
      'data-plate': "{{ $invoice->vehicle->plate_number }}",
      'data-model': "{{ $invoice->vehicle->model }}",
      'data-year': "{{ $invoice->vehicle->year }}",
      'data-color': "{{ $invoice->vehicle->color }}",
      'data-odometer': "{{ $invoice->vehicle->odometer }}"
    });
    $('#vehicle_id').append(soVehOpt).trigger('change');
    @endif

    // Show/hide client/vehicle fields
    function toggleFields() {
        let manualCustomer = $('input[name="customer_name"]').val().trim();
        let manualVehicle = $('input[name="vehicle_name"]').val().trim();
        let clientSelected = $('#client_id').val();
        let vehicleSelected = $('#vehicle_id').val();

        if (manualCustomer !== '' || manualVehicle !== '') {
            $('#client_id').closest('.col-md-3').hide();
            $('#vehicle_id').closest('.col-md-3').hide();
            $('input[name="customer_name"]').closest('.col-md-3').show();
            $('input[name="vehicle_name"]').closest('.col-md-3').show();
        } else if (clientSelected || vehicleSelected) {
            $('input[name="customer_name"]').closest('.col-md-3').hide();
            $('input[name="vehicle_name"]').closest('.col-md-3').hide();
            $('#client_id').closest('.col-md-3').show();
            $('#vehicle_id').closest('.col-md-3').show();
        } else {
            $('#client_id').closest('.col-md-3').show();
            $('#vehicle_id').closest('.col-md-3').show();
            $('input[name="customer_name"]').closest('.col-md-3').show();
            $('input[name="vehicle_name"]').closest('.col-md-3').show();
        }
    }

    toggleFields();

    $('input[name="customer_name"], input[name="vehicle_name"]').on('input', toggleFields);
    $('#client_id, #vehicle_id').on('change', toggleFields);
});
</script>
<script>
function quickUpdateStatus(selectEl, id) {
    const form = selectEl.closest('form');
    form.submit();
    // optionally remove row immediately
    $(selectEl).closest('tr').fadeOut(300, function() { $(this).remove(); });
}
</script>


@endsection
