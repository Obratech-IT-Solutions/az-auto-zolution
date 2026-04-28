{{-- resources/views/cashier/inventory.blade.php --}}
@extends('layouts.cashier')

@section('title', 'Inventory Management')

@section('content')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@include('cashier.partials.cashier-flash-toast')
  <style>
    .table td,
    .table th {
    vertical-align: middle;
    }

    .table th,
    .table td {
    text-align: center;
    }

    #deleteInventoryModal .modal-dialog {
    margin: auto;
    }
  </style>

  <div class="container mt-4">
    <h2 class="mb-4">Inventory Management</h2>

    <div class="card mb-5">
    <div class="card-header bg-success text-white" id="form-header">
      Add New Inventory Item
    </div>
    <div class="card-body">
      <form id="inventoryForm" action="{{ route('cashier.inventory.store') }}" method="POST">
      @csrf
      <input type="hidden" id="inventory_id" name="inventory_id">
      <div class="row row-cols-lg-auto g-2 align-items-end">
        <div class="col">
        <input type="text" id="item_name" name="item_name" class="form-control" placeholder="Item Name" required>
        </div>
        <div class="col">
        <input type="text" id="part_number" name="part_number" class="form-control" placeholder="Part Number"
          required>
        </div>
        <div class="col">
        <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Quantity" required>
        </div>
        <div class="col">
        <input type="number" step="0.01" id="selling" name="selling" class="form-control"
          placeholder="Selling Price" required>
        </div>
        <div class="col">
        <input type="number" step="0.01" id="acquisition_price" name="acquisition_price" class="form-control"
          placeholder="Acquisition Price">
        </div>
        <div class="col">
        <input type="text" id="supplier" name="supplier" class="form-control" placeholder="Supplier">
        </div>
        <div class="col">
        <button type="submit" class="btn btn-light" id="submitBtn">Add</button>

        </div>
      </div>
      </form>
    </div>
    </div>

    {{-- ➤ Inventory List --}}
    <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span>Inventory List</span>
      <div class="d-flex">
      <input id="searchInput" type="search" class="form-control form-control-sm" placeholder="Search name, part #, supplier…"
        value="{{ request('q', '') }}" autocomplete="off" style="min-width: 220px;">
      </div>

    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <small>
        <table id="inventoryTable" class="table table-hover table-bordered align-middle table-sm mb-0">

        <thead class="table-primary">
          <tr>
          <th>#</th>
          <th>Item Name</th>
          <th>Part #</th>
          <th>Qty</th>
          <th>Selling (₱)</th>
          <th>Acquisition (₱)</th>
          <th>Supplier</th>
          <th class="text-center">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse($inventories as $inv)
        <tr data-id="{{ $inv->id }}"
        class="{{ $inv->quantity == 0 ? 'table-danger' : ($inv->quantity < 3 ? 'table-warning' : 'table-light') }}">


        <td>{{ ($inventories->currentPage() - 1) * $inventories->perPage() + $loop->iteration }}</td>
        <td>{{ $inv->item_name }}</td>
        <td>{{ $inv->part_number }}</td>
        <td class="text-end">{{ $inv->quantity }}</td>
        <td class="text-end">{{ number_format($inv->selling, 2) }}</td>
        <td class="text-end">
        {{ $inv->acquisition_price ? number_format($inv->acquisition_price, 2) : '-' }}
        </td>
        <td>{{ $inv->supplier ?? '-' }}</td>
        <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-info edit-btn" data-id="{{ $inv->id }}"
        data-item_name="{{ $inv->item_name }}" data-part_number="{{ $inv->part_number }}"
        data-quantity="{{ $inv->quantity }}" data-selling="{{ $inv->selling }}"
        data-acquisition_price="{{ $inv->acquisition_price }}" data-supplier="{{ $inv->supplier }}"
        title="Edit">
        <i class="bi bi-pencil-square"></i>
        </button>
        <button type="button"
          class="btn btn-sm btn-outline-danger inventory-delete-trigger"
          title="Delete"
          data-delete-url="{{ route('cashier.inventory.destroy', $inv) }}">
          <i class="bi bi-trash"></i>
        </button>
        </td>

        </tr>
      @empty
        <tr>
        <td colspan="8" class="text-center text-muted py-3">
        @if(request()->filled('q'))
          No items match your search.
        @else
          No inventory items yet.
        @endif
        </td>
        </tr>
      @endforelse
        </tbody>
        </table>
      </small>
      </div>
    </div>
    @if($inventories->total() > 0)
    <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">{{ $inventories->total() }} item(s) total</small>
      {{ $inventories->onEachSide(1)->links() }}
    </div>
    @endif
    </div>
  </div>

  <div class="modal fade" id="deleteInventoryModal" tabindex="-1" aria-labelledby="deleteInventoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content border-0 shadow-lg overflow-hidden">
        <div class="modal-header bg-danger text-white border-0 py-3 position-relative">
          <h5 class="modal-title w-100 text-center fs-6 mb-0 pe-4" id="deleteInventoryModalLabel">Confirm delete</h5>
          <button type="button" class="btn-close btn-close-white position-absolute top-50 end-0 translate-middle-y me-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body px-3 py-4 text-center">
          <p class="small text-muted mb-0">Remove this inventory item? This cannot be undone.</p>
        </div>
        <div class="modal-footer border-0 justify-content-center gap-2 pb-4 pt-0 flex-nowrap">
          <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger btn-sm px-3" id="confirmInventoryDeleteBtn">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Inventory Modal -->
  <div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="editInventoryModalLabel">Edit Inventory Item</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <form id="editInventoryForm">
        <input type="hidden" id="edit_inventory_id">
        <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Item Name</label>
          <input type="text" id="edit_item_name" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Part Number</label>
          <input type="text" id="edit_part_number" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Quantity</label>
          <input type="number" id="edit_quantity" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Selling Price</label>
          <input type="number" step="0.01" id="edit_selling" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Acquisition Price</label>
          <input type="number" step="0.01" id="edit_acquisition_price" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Supplier</label>
          <input type="text" id="edit_supplier" class="form-control">
        </div>
        </div>
      </form>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      <button type="button" id="saveEditBtn" class="btn btn-primary">Save Changes</button>
      </div>
    </div>
    </div>
  </div>


  {{-- AJAX + Edit + Search Scripts --}}
  <script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let editingId = null;
    let pendingDeleteUrl = null;

    function showInvToastGreen(text) {
      if (typeof showCashierFlashToast === 'function') {
        showCashierFlashToast(text, { variant: 'success', reloadAfter: true });
      } else {
        window.location.reload();
      }
    }

    function showInvToastDeletedThenReload() {
      if (typeof showCashierFlashToast === 'function') {
        showCashierFlashToast('Deleted.', { variant: 'danger', reloadAfter: true });
      } else {
        window.location.reload();
      }
    }

    // --- ADD/UPDATE Inventory (AJAX) ---
    document.getElementById('inventoryForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const url = form.action;
    const method = 'POST';

    const data = Object.fromEntries(new FormData(form));
    form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));

    try {
      const res = await fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(data)
      });

      if (res.ok) {
      await res.json();
      showInvToastGreen('Added');
      form.reset();
      } else if (res.status === 422) {
      const errs = (await res.json()).errors;
      Object.entries(errs).forEach(([field, msgs]) => {
        const inp = form.querySelector(`[name="${field}"]`);
        if (inp) {
        inp.classList.add('is-invalid');
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        fb.textContent = msgs.join(' ');
        inp.parentNode.appendChild(fb);
        }
      });
      } else {
      alert('Server error.');
      }
    } catch {
      alert('Network error.');
    }
    });


    document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.getElementById('edit_inventory_id').value = this.dataset.id;
      document.getElementById('edit_item_name').value = this.dataset.item_name;
      document.getElementById('edit_part_number').value = this.dataset.part_number;
      document.getElementById('edit_quantity').value = this.dataset.quantity;
      document.getElementById('edit_selling').value = this.dataset.selling;
      document.getElementById('edit_acquisition_price').value = this.dataset.acquisition_price;
      document.getElementById('edit_supplier').value = this.dataset.supplier;

      let modal = new bootstrap.Modal(document.getElementById('editInventoryModal'));
      modal.show();
    });
    });




    (function () {
      const el = document.getElementById('searchInput');
      if (!el) return;
      let t;
      el.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => {
          const url = new URL(window.location.href);
          const v = el.value.trim();
          if (v) url.searchParams.set('q', v); else url.searchParams.delete('q');
          url.searchParams.delete('page');
          window.location.assign(url.toString());
        }, 400);
      });
    })();


    document.getElementById('saveEditBtn').addEventListener('click', async () => {
    const id = document.getElementById('edit_inventory_id').value;
    const data = {
      item_name: document.getElementById('edit_item_name').value,
      part_number: document.getElementById('edit_part_number').value,
      quantity: document.getElementById('edit_quantity').value,
      selling: document.getElementById('edit_selling').value,
      acquisition_price: document.getElementById('edit_acquisition_price').value,
      supplier: document.getElementById('edit_supplier').value,
    };

    try {
      const res = await fetch(`/cashier/inventory/${id}`, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ ...data, _token: token })
      });
      if (res.ok) {
      var em = bootstrap.Modal.getInstance(document.getElementById('editInventoryModal'));
      if (em) em.hide();
      showInvToastGreen('Updated');
      } else if (res.status === 422) {
      alert('Validation failed. Check fields.');
      } else if (res.status === 419) {
      alert('Session expired. Please refresh the page and try again.');
      } else if (res.status === 403) {
      alert('You do not have permission to update this item.');
      } else {
      alert('Server error.');
      }
    } catch {
      alert('Network error.');
    }
    });

    document.querySelectorAll('.inventory-delete-trigger').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pendingDeleteUrl = btn.getAttribute('data-delete-url');
        new bootstrap.Modal(document.getElementById('deleteInventoryModal')).show();
      });
    });

    document.getElementById('confirmInventoryDeleteBtn').addEventListener('click', async function () {
      var url = pendingDeleteUrl;
      var modalEl = document.getElementById('deleteInventoryModal');
      var inst = bootstrap.Modal.getInstance(modalEl);
      if (inst) {
        inst.hide();
      }
      pendingDeleteUrl = null;
      if (!url) {
        return;
      }
      try {
        var res = await fetch(url, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ _token: token }),
        });
        if (res.ok) {
          showInvToastDeletedThenReload();
        } else {
          alert('Could not delete this item.');
        }
      } catch (_e) {
        alert('Network error.');
      }
    });

  </script>
@endsection