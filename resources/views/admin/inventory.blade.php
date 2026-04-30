@extends('layouts.admin')

@section('title', 'Inventory Management')

@section('content')
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <div class="container mt-4">
    <h2 class="mb-4">Inventory Management</h2>

    {{-- Success Toast --}}
    <div id="inventory-success" class="alert alert-success d-none"></div>

    {{-- Add/Update Form --}}
    <div class="card mb-5">
    <div class="card-header bg-success text-white" id="form-header">
      Add New Inventory Item
    </div>
    <div class="card-body">
      <form id="inventoryForm" action="{{ route('admin.inventory.store') }}" method="POST">
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
        <button type="button" class="btn btn-secondary d-none" id="cancelEditBtn">Cancel</button>
        </div>
      </div>
      </form>
    </div>
    </div>

    {{-- Inventory List --}}
    @php
      $invSort = \App\Models\Inventory::normalizeIndexSort(request('sort'));
      $invSortLabels = \App\Models\Inventory::indexSortLabels();
    @endphp
    <div class="card">
    <div class="card-header bg-primary text-white py-2 px-3">
      <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 gap-md-3">
      <span class="fw-semibold text-nowrap me-md-auto">Inventory List</span>
      <div class="d-flex align-items-center gap-2 flex-nowrap min-w-0 ms-md-auto">
      <div class="input-group input-group-sm flex-grow-1 min-w-0" style="max-width: 22rem;">
        <span class="input-group-text bg-light border-secondary-subtle py-0 d-none d-sm-inline-flex" aria-hidden="true"><i class="bi bi-search"></i></span>
        <input id="searchInput" name="q" type="search" class="form-control" autocomplete="off"
          placeholder="Search name, part #, supplier…" value="{{ request('q', '') }}"
          aria-label="Search inventory" inputmode="search">
      </div>
      <div class="dropdown flex-shrink-0">
        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
          data-bs-auto-close="outside" aria-expanded="false" aria-label="Filter and sort inventory list">
          {{ $invSortLabels[$invSort] ?? 'Newest' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          @foreach($invSortLabels as $sortKey => $sortLabel)
          <li>
            <a class="dropdown-item @if($invSort === $sortKey) active fw-semibold @endif"
              href="{{ route('admin.inventory', array_merge(request()->only('q'), ['sort' => $sortKey])) }}">{{ $sortLabel }}</a>
          </li>
          @endforeach
        </ul>
      </div>
      </div>
      </div>
    </div>
    <div class="card-body p-0">
      <table id="inventoryTable" class="table mb-0">
      <thead class="table-light">
        <tr>
        <th>#</th>
        <th>Item Name</th>
        <th>Part #</th>
        <th>Qty</th>
        <th title="Units billed on paid invoices (linked part)">Sold</th>
        <th>Selling (₱)</th>
        <th>Acquisition (₱)</th>
        <th>Supplier</th>
        <th class="text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($inventories as $inv)
      <tr data-id="{{ $inv->id }}"
      class="{{ $inv->quantity == 0 ? 'table-danger' : ($inv->quantity < 3 ? 'table-warning' : '') }}">

      <td>{{ ($inventories->currentPage() - 1) * $inventories->perPage() + $loop->iteration }}</td>
      <td>{{ $inv->item_name }}</td>
      <td>{{ $inv->part_number }}</td>
      <td class="text-end">{{ $inv->quantity }}</td>
      <td class="text-end fw-semibold">{{ (int) ($inv->sold_qty ?? 0) }}</td>
      <td class="text-end">{{ number_format($inv->selling, 2) }}</td>
      <td>{{ $inv->acquisition_price ? number_format($inv->acquisition_price, 2) : '-' }}</td>
      <td>{{ $inv->supplier ?? '-' }}</td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-info edit-btn" data-id="{{ $inv->id }}"
        data-item_name="{{ $inv->item_name }}" data-part_number="{{ $inv->part_number }}"
        data-quantity="{{ $inv->quantity }}" data-selling="{{ $inv->selling }}"
        data-acquisition_price="{{ $inv->acquisition_price }}" data-supplier="{{ $inv->supplier }}"
        title="Edit">
        <i class="bi bi-pencil-square"></i>
        </button>
        <form class="d-inline" action="{{ route('admin.inventory.destroy', $inv) }}" method="POST"
        onsubmit="return confirm('Delete this item?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" title="Delete">
        <i class="bi bi-trash"></i>
        </button>
        </form>
      </td>

      </tr>
      @empty
      <tr>
      <td colspan="9" class="text-center text-muted py-3">
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
    </div>
    @if($inventories->total() > 0)
    <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">{{ $inventories->total() }} item(s) total</small>
      {{ $inventories->onEachSide(1)->links() }}
    </div>
    @endif
    </div>
  </div>

  {{-- AJAX + Edit + Search Scripts --}}
  <script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let editingId = null;

    // Inventory form handler (add/update)
    document.getElementById('inventoryForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const id = editingId;
    const url = id
      ? `/admin/inventory/${id}`
      : form.action;
    const method = id ? 'PUT' : 'POST';

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
      const inv = await res.json();
      const alert = document.getElementById('inventory-success');
      alert.textContent = id ? '✔ Inventory updated!' : '✔ Inventory added!';
      alert.classList.remove('d-none');
      setTimeout(() => alert.classList.add('d-none'), 2500);
      window.location.reload();
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

    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      editingId = this.dataset.id;
      ['item_name', 'part_number', 'quantity', 'selling', 'acquisition_price', 'supplier']
      .forEach(f => document.getElementById(f).value = this.dataset[f] || '');
      document.getElementById('inventory_id').value = editingId;
      document.getElementById('form-header').textContent = 'Edit Inventory Item';
      document.getElementById('submitBtn').textContent = 'Update';
      document.getElementById('cancelEditBtn').classList.remove('d-none');
    });
    });

    // Cancel edit
    document.getElementById('cancelEditBtn').addEventListener('click', () => {
    editingId = null;
    document.getElementById('inventoryForm').reset();
    document.getElementById('form-header').textContent = 'Add New Inventory Item';
    document.getElementById('submitBtn').textContent = 'Add';
    document.getElementById('cancelEditBtn').classList.add('d-none');
    });

    (function () {
      const el = document.getElementById('searchInput');
      if (!el) return;

      const DEBOUNCE_MS = 700;

      function currentQueryParam() {
        return new URL(window.location.href).searchParams.get('q') ?? '';
      }

      function navigateIfSearchChanged(trimmed) {
        if (trimmed === currentQueryParam()) {
          return;
        }
        const url = new URL(window.location.href);
        if (trimmed) {
          url.searchParams.set('q', trimmed);
        } else {
          url.searchParams.delete('q');
        }
        url.searchParams.delete('page');
        window.location.assign(url.toString());
      }

      let t;
      el.addEventListener('input', function () {
        clearTimeout(t);
        t = window.setTimeout(function () {
          navigateIfSearchChanged(el.value.trim());
        }, DEBOUNCE_MS);
      });

      el.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          clearTimeout(t);
          navigateIfSearchChanged(el.value.trim());
        }
      });

      el.addEventListener('search', function () {
        clearTimeout(t);
        navigateIfSearchChanged(el.value.trim());
      });
    })();

  </script>
@endsection