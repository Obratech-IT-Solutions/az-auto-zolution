{{-- resources/views/cashier/vehicle.blade.php --}}
@extends('layouts.cashier')

@section('content')
@include('cashier.partials.cashier-flash-toast')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    .card {
    border-radius: 1rem;
    }

    .modal-content {
    border-radius: .8rem;
    }

    .alert {
    border-radius: .6rem;
    }

    #clientsTable tbody tr:hover,
    #vehiclesTable tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    cursor: pointer;
    }

    .alert {
    transition: all 0.5s ease;
    }

    .fixed-section {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: #f6f8fa;
    padding-bottom: 1rem;
    }

    .scrollable-tables {
    max-height: calc(100vh - 380px);
    /* Adjust based on your header size */
    overflow-y: auto;
    padding-top: 1rem;
    }
  </style>

  <div class="container">
    <h2 class="mb-4 fw-bold text-primary"><i class="bi bi-person-vcard"></i> Clients & Vehicle Management</h2>


    {{-- Success toasts --}}
    <div id="client-success" class="alert alert-success d-none shadow-sm">
    <i class="bi bi-check-circle-fill me-2"></i> Client added!
    </div>

    <div id="vehicle-success" class="alert alert-success d-none">✔ Vehicle added!</div>

    {{-- Add Client --}}
    <div class="card mb-4">
    <div class="card-header bg-success text-white d-flex align-items-center">
      <i class="bi bi-person-plus me-2"></i> <span class="fw-semibold">Add New Client</span>
    </div>

    <div class="card-body">
      <form id="clientForm" method="POST" action="{{ route('cashier.clients.store') }}">
      @csrf
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
        <input type="text" name="name" class="form-control shadow-sm rounded" placeholder="Name" required>
        </div>
        <div class="col-md-3">
        <input type="text" name="address" class="form-control shadow-sm rounded" placeholder="Address">
        </div>
        <div class="col-md-2">
        <input type="text" name="phone" class="form-control shadow-sm rounded" placeholder="Phone">
        </div>
        <div class="col-md-3">
        <input type="email" name="email" class="form-control shadow-sm rounded" placeholder="Email">
        </div>
        <div class="col-md-1">
        <button type="submit" class="btn btn-light w-100">Add</button>
        </div>
      </div>
      </form>
    </div>
    </div>

    {{-- Add Vehicle --}}
    <div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex align-items-center">
      <i class="bi bi-truck me-2"></i> <span class="fw-semibold">Add New Vehicle</span>
    </div>

    <div class="card-body">
      <form id="vehicleForm" method="POST" action="{{ route('cashier.vehicles.store') }}">
      @csrf
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
        <select name="client_id" class="form-select shadow-sm rounded">
          <option value="">Select Client</option>
          @foreach($clientsForSelect as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
        </select>
        </div>
        <div class="col-md-2">
        <input type="text" name="plate_number" class="form-control shadow-sm rounded" placeholder="Plate #"
          required>
        </div>
        <div class="col-md-2">
        <input type="text" name="model" class="form-control shadow-sm rounded" placeholder="Model">
        </div>
        <div class="col-md-2">
        <input type="text" name="vin_chasis" class="form-control shadow-sm rounded" placeholder="VIN/Chasis">
        </div>
        <div class="col-md-2">
        <input type="text" name="manufacturer" class="form-control shadow-sm rounded" placeholder="Manufacturer">
        </div>
        <div class="col-md-1">
        <input type="text" name="year" class="form-control shadow-sm rounded" placeholder="Year">
        </div>
        <div class="col-md-1">
        <input type="text" name="color" class="form-control shadow-sm rounded" placeholder="Color">
        </div>
        <div class="col-md-2 mt-2 mt-md-0">
        <input type="text" name="odometer" class="form-control shadow-sm rounded" placeholder="Odometer">
        </div>
        <div class="col-md-1">
        <button type="submit" class="btn btn-light w-100">Add</button>
        </div>
      </div>
      </form>
    </div>
    </div>

    {{-- Clients Table --}}
    <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center sticky-top bg-white"
      style="top: 0; z-index: 1020;">
      <span>Clients List</span>
      <input id="clientSearch" type="text" class="form-control form-control-sm shadow-sm rounded"
      placeholder="🔍 Search client..." style="width: 220px;" value="{{ request('client_q', '') }}"
      autocomplete="off">
    </div>
    <div class="scrollable-tables card-body p-0">
      <table id="clientsTable" class="table mb-0 table-hover">
      <thead class="table-light">
        <tr>
        <th>Name</th>
        <th>Address</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($clients as $c)
      <tr class="client-row" data-id="{{ $c->id }}">
      <td>{{ $c->name }}</td>
      <td>{{ $c->address }}</td>
      <td>{{ $c->phone }}</td>
      <td>{{ $c->email }}</td>
      <td>
        <button class="btn btn-sm btn-warning edit-client" data-id="{{ $c->id }}" data-name="{{ $c->name }}"
        data-address="{{ $c->address }}" data-phone="{{ $c->phone }}" data-email="{{ $c->email }}">
        <i class="bi bi-pencil-square"></i>
        </button>
        <button class="btn btn-sm btn-danger delete-client" data-id="{{ $c->id }}">
        <i class="bi bi-trash"></i>
        </button>
      </td>
      </tr>
      @endforeach
      </tbody>
      </table>
    </div>
    <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">{{ $clients->total() }} client(s) total</small>
      {{ $clients->onEachSide(1)->links() }}
    </div>
    </div>


    <br>
    {{-- Vehicles Table --}}
    <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center sticky-top bg-white"
      style="top: 0; z-index: 1020;">
      <span>Vehicles List</span>
      <input id="vehicleSearch" type="text" class="form-control form-control-sm" placeholder="Search vehicle..."
      style="width: 200px;" value="{{ request('vehicle_q', '') }}" autocomplete="off">
    </div>
    <div class="scrollable-tables card-body p-0">
      <table id="vehiclesTable" class="table mb-0 table-hover">
      <thead class="table-light">
        <tr>
        <th>Client</th>
        <th>Plate #</th>
        <th>Model</th>
        <th>VIN/Chasis</th>
        <th>Manufacturer</th>
        <th>Year</th>
        <th>Color</th>
        <th>Odometer</th>
        <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($vehicles as $v)
      <tr>
      <td>{{ optional($v->client)->name ?? '-' }}</td>
      <td>{{ $v->plate_number }}</td>
      <td>{{ $v->model }}</td>
      <td>{{ $v->vin_chasis }}</td>
      <td>{{ $v->manufacturer ?? '-' }}</td>
      <td>{{ $v->year ?? '-' }}</td>
      <td>{{ $v->color ?? '-' }}</td>
      <td>{{ $v->odometer }}</td>
      <td>
        <button class="btn btn-sm btn-warning edit-vehicle" data-id="{{ $v->id }}"
        data-client_id="{{ $v->client_id }}" data-plate_number="{{ $v->plate_number }}"
        data-model="{{ $v->model }}" data-vin_chasis="{{ $v->vin_chasis }}"
        data-manufacturer="{{ $v->manufacturer }}" data-year="{{ $v->year }}" data-color="{{ $v->color }}"
        data-odometer="{{ $v->odometer }}">
        <i class="bi bi-pencil-square"></i>
        </button>
        <button class="btn btn-sm btn-danger delete-vehicle" data-id="{{ $v->id }}">
        <i class="bi bi-trash"></i>
        </button>
      </td>
      </tr>
      @endforeach
      </tbody>
      </table>
    </div>
    <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">{{ $vehicles->total() }} vehicle(s) total</small>
      {{ $vehicles->onEachSide(1)->links() }}
    </div>
    </div>


    <!-- View Client Details Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i> Client Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="clientInfo"></div>
        <h6 class="mt-4">Vehicles:</h6>
        <ul id="clientVehicles" class="list-group list-group-flush"></ul>
      </div>
      </div>
    </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="editClientForm" class="modal-content">
      @csrf
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id">
        <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
        <input type="text" name="address" class="form-control mb-2" placeholder="Address">
        <input type="text" name="phone" class="form-control mb-2" placeholder="Phone">
        <input type="email" name="email" class="form-control mb-2" placeholder="Email">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
      </form>
    </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="editVehicleForm" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Edit Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id">
        <select name="client_id" class="form-select shadow-sm rounded">
        <option value="">Select Client</option>
        @foreach($clientsForSelect as $c)
      <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
        </select>
        <input type="text" name="plate_number" class="form-control mb-2" placeholder="Plate #" required>
        <input type="text" name="model" class="form-control mb-2" placeholder="Model">
        <input type="text" name="vin_chasis" class="form-control mb-2" placeholder="VIN/Chasis">
        <input type="text" name="manufacturer" class="form-control mb-2" placeholder="Manufacturer">
        <input type="text" name="year" class="form-control mb-2" placeholder="Year">
        <input type="text" name="color" class="form-control mb-2" placeholder="Color">
        <input type="text" name="odometer" class="form-control mb-2" placeholder="Odometer">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
      </form>
    </div>
    </div>

    <!-- Delete confirmation (replaces window.confirm) -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalTitle"
      aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger overflow-hidden shadow">
      <div class="modal-header bg-danger text-white border-0 rounded-0">
        <h5 class="modal-title d-flex align-items-center gap-2" id="deleteConfirmModalTitle">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span id="deleteConfirmTitleLabel">Confirm delete</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
        aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="deleteConfirmMessage" class="mb-0 text-body"></p>
      </div>
      <div class="modal-footer bg-light border-top">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="deleteConfirmProceed">
        <i class="bi bi-trash me-1"></i>Delete
        </button>
      </div>
      </div>
    </div>
    </div>

  </div>
  </div>
  </div>

  <script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const deleteConfirmModalEl = document.getElementById('deleteConfirmModal');
    function getDeleteConfirmModal() {
      if (!deleteConfirmModalEl || typeof window.bootstrap === 'undefined') return null;
      return bootstrap.Modal.getOrCreateInstance(deleteConfirmModalEl);
    }
    let pendingDelete = null;

    async function ajaxForm(formId, tableId, successAlertId, rowBuilder, afterRowCreate) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
      });
      if (res.ok) {
        const obj = await res.json();
        const tbody = document.querySelector(`#${tableId} tbody`);
        const tr = document.createElement('tr');
        tr.innerHTML = rowBuilder(obj);
        if (typeof afterRowCreate === 'function') {
          afterRowCreate(tr, obj);
        }
        tbody.prepend(tr);
        const alert = document.getElementById(successAlertId);
        alert.classList.remove('d-none');
        setTimeout(() => alert.classList.add('d-none'), 3000);
        form.reset();
      } else if (res.status === 422) {
        const errors = (await res.json()).errors;
        for (let [field, msgs] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
          input.classList.add('is-invalid');
          const fb = document.createElement('small');
          fb.className = 'text-danger';
          fb.textContent = msgs.join(' ');
          input.parentNode.appendChild(fb);
        }
        }
      } else {
        alert('Server error.');
      }
      } catch {
      alert('Network error.');
      }
    });
    }

    ajaxForm(
    'clientForm',
    'clientsTable',
    'client-success',
    client => {
      const vehicleSelect = document.querySelector('#vehicleForm select[name="client_id"]');
      if (vehicleSelect) {
      const opt = document.createElement('option');
      opt.value = client.id;
      opt.textContent = client.name;
      vehicleSelect.appendChild(opt);
      }
      const esc = (s) => String(s ?? '').replace(/"/g, '&quot;');
      return `
      <td>${client.name}</td>
      <td>${client.address || ''}</td>
      <td>${client.phone || ''}</td>
      <td>${client.email || ''}</td>
      <td>
        <button type="button" class="btn btn-sm btn-warning edit-client" data-id="${client.id}" data-name="${esc(client.name)}" data-address="${esc(client.address)}" data-phone="${esc(client.phone)}" data-email="${esc(client.email)}">
        <i class="bi bi-pencil-square"></i>
        </button>
        <button type="button" class="btn btn-sm btn-danger delete-client" data-id="${client.id}">
        <i class="bi bi-trash"></i>
        </button>
      </td>`;
    },
    (tr, client) => {
      tr.classList.add('client-row');
      tr.dataset.id = String(client.id);
    }
    );

    ajaxForm(
    'vehicleForm',
    'vehiclesTable',
    'vehicle-success',
    v => {
      const cn = v.client ? v.client.name : '-';
      const esc = (s) => String(s ?? '').replace(/"/g, '&quot;');
      return `
      <td>${cn}</td>
      <td>${v.plate_number}</td>
      <td>${v.model || ''}</td>
      <td>${v.vin_chasis || ''}</td>
      <td>${v.manufacturer || '-'}</td>
      <td>${v.year || '-'}</td>
      <td>${v.color || '-'}</td>
      <td>${v.odometer || ''}</td>
      <td>
        <button type="button" class="btn btn-sm btn-warning edit-vehicle" data-id="${v.id}"
        data-client_id="${v.client_id ?? ''}" data-plate_number="${esc(v.plate_number)}"
        data-model="${esc(v.model)}" data-vin_chasis="${esc(v.vin_chasis)}"
        data-manufacturer="${esc(v.manufacturer)}" data-year="${esc(v.year)}" data-color="${esc(v.color)}"
        data-odometer="${esc(v.odometer)}">
        <i class="bi bi-pencil-square"></i>
        </button>
        <button type="button" class="btn btn-sm btn-danger delete-vehicle" data-id="${v.id}">
        <i class="bi bi-trash"></i>
        </button>
      </td>`;
    }
    );

    (function debounceServerSearch(inputId, param, pageParam) {
      const el = document.getElementById(inputId);
      if (!el) return;
      let timer;
      el.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          const url = new URL(window.location.href);
          url.searchParams.set(param, el.value);
          url.searchParams.delete(pageParam);
          window.location.assign(url.toString());
        }, 400);
      });
    })('clientSearch', 'client_q', 'clients_page');

    (function debounceServerSearch(inputId, param, pageParam) {
      const el = document.getElementById(inputId);
      if (!el) return;
      let timer;
      el.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
          const url = new URL(window.location.href);
          url.searchParams.set(param, el.value);
          url.searchParams.delete(pageParam);
          window.location.assign(url.toString());
        }, 400);
      });
    })('vehicleSearch', 'vehicle_q', 'vehicles_page');

    // Show client info on row click (delegation: works for AJAX-added rows)
    document.querySelector('#clientsTable tbody').addEventListener('click', async (ev) => {
      const row = ev.target.closest('tr.client-row');
      if (!row || ev.target.closest('button')) return;
      const clientId = row.dataset.id;
      try {
      let res = await fetch(`/cashier/clients/${clientId}/vehicles`, {
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
      });

      if (res.ok) {
        let data = await res.json();
        document.getElementById('clientInfo').innerHTML = `
      <div><strong>Name:</strong> ${data.client.name}</div>
      <div><strong>Address:</strong> ${data.client.address || '-'}</div>
      <div><strong>Phone:</strong> ${data.client.phone || '-'}</div>
      <div><strong>Email:</strong> ${data.client.email || '-'}</div>
      `;

        let list = document.getElementById('clientVehicles');
        list.innerHTML = data.vehicles.length
        ? data.vehicles.map(v => `
      <li class="list-group-item">
      <strong>${v.plate_number}</strong> - ${v.model || ''} (${v.year || ''})
      </li>
      `).join('')
        : '<li class="list-group-item text-muted">No vehicles</li>';

        new bootstrap.Modal(document.getElementById('viewClientModal')).show();
      } else {
        alert('Could not load client data.');
      }
      } catch {
      alert('Network error.');
      }
    });

    document.querySelector('#clientsTable tbody').addEventListener('click', (e) => {
      const btn = e.target.closest('.delete-client');
      if (!btn) return;
      e.stopPropagation();
      const modal = getDeleteConfirmModal();
      if (!modal) return;
      pendingDelete = { type: 'client', btn };
      document.getElementById('deleteConfirmTitleLabel').textContent = 'Delete client?';
      document.getElementById('deleteConfirmMessage').textContent =
        'This will permanently remove this client from the list. This cannot be undone.';
      modal.show();
    });

    document.querySelector('#vehiclesTable tbody').addEventListener('click', (e) => {
      const btn = e.target.closest('.delete-vehicle');
      if (!btn) return;
      e.stopPropagation();
      const modal = getDeleteConfirmModal();
      if (!modal) return;
      pendingDelete = { type: 'vehicle', btn };
      document.getElementById('deleteConfirmTitleLabel').textContent = 'Delete vehicle?';
      document.getElementById('deleteConfirmMessage').textContent =
        'This will permanently remove this vehicle from the list. This cannot be undone.';
      modal.show();
    });

    document.getElementById('deleteConfirmProceed').addEventListener('click', async function () {
      if (!pendingDelete) return;
      const { type, btn } = pendingDelete;
      pendingDelete = null;
      const dm = getDeleteConfirmModal();
      if (dm) dm.hide();
      const url = type === 'client'
        ? `/cashier/clients/${btn.dataset.id}`
        : `/cashier/vehicles/${btn.dataset.id}`;
      try {
        const res = await fetch(url, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
        });
        if (res.ok) {
          if (typeof showCashierFlashToast === 'function') {
            showCashierFlashToast(type === 'client' ? 'Client deleted.' : 'Vehicle deleted.', { variant: 'danger', reloadAfter: true });
          } else {
          btn.closest('tr').remove();
          }
        } else {
          alert(type === 'client' ? 'Failed to delete client.' : 'Failed to delete vehicle.');
        }
      } catch {
        alert('Network error.');
      }
    });

    if (deleteConfirmModalEl) {
      deleteConfirmModalEl.addEventListener('hidden.bs.modal', function () {
        pendingDelete = null;
      });
    }

    document.querySelector('#clientsTable tbody').addEventListener('click', (e) => {
      const btn = e.target.closest('.edit-client');
      if (!btn) return;
      e.stopPropagation();
      const modal = document.getElementById('editClientModal');
      modal.querySelector('[name=id]').value = btn.dataset.id;
      modal.querySelector('[name=name]').value = btn.dataset.name;
      modal.querySelector('[name=address]').value = btn.dataset.address;
      modal.querySelector('[name=phone]').value = btn.dataset.phone;
      modal.querySelector('[name=email]').value = btn.dataset.email;
      new bootstrap.Modal(modal).show();
    });


    document.getElementById('editClientForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    let form = this;
    let id = form.querySelector('[name=id]').value;
    let data = Object.fromEntries(new FormData(form).entries());
    let res = await fetch(`/cashier/clients/${id}`, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    if (res.ok) {
      if (typeof showCashierFlashToast === 'function') {
        showCashierFlashToast('Client updated.', { variant: 'success', reloadAfter: true });
      } else {
      location.reload();
      }
    }
    });

    document.querySelector('#vehiclesTable tbody').addEventListener('click', (e) => {
      const btn = e.target.closest('.edit-vehicle');
      if (!btn) return;
      e.stopPropagation();
      const modal = document.getElementById('editVehicleModal');
      modal.querySelector('[name=id]').value = btn.dataset.id;
      modal.querySelector('[name=client_id]').value = btn.dataset.client_id || '';
      modal.querySelector('[name=plate_number]').value = btn.dataset.plate_number;
      modal.querySelector('[name=model]').value = btn.dataset.model;
      modal.querySelector('[name=vin_chasis]').value = btn.dataset.vin_chasis;
      modal.querySelector('[name=manufacturer]').value = btn.dataset.manufacturer;
      modal.querySelector('[name=year]').value = btn.dataset.year;
      modal.querySelector('[name=color]').value = btn.dataset.color;
      modal.querySelector('[name=odometer]').value = btn.dataset.odometer;
      new bootstrap.Modal(modal).show();
    });

    document.getElementById('editVehicleForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    let form = this;
    let id = form.querySelector('[name=id]').value;
    let data = Object.fromEntries(new FormData(form).entries());
    let res = await fetch(`/cashier/vehicles/${id}`, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    if (res.ok) {
      if (typeof showCashierFlashToast === 'function') {
        showCashierFlashToast('Vehicle updated.', { variant: 'success', reloadAfter: true });
      } else {
      location.reload();
      }
    }
    });
  </script>

@endsection