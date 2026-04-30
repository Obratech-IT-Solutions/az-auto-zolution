{{-- resources/views/cashier/inventory.blade.php --}}
@extends('layouts.cashier')

@section('title', 'Inventory Management')

@section('content')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@include('cashier.partials.cashier-flash-toast')
  <style>
    #deleteInventoryModal .modal-dialog {
      margin: auto;
    }

    /* Match cashier invoice index lists (.invoice-index-list .history-list-table) */
    .inventory-index-list .history-list-table {
      table-layout: fixed;
      width: 100%;
    }

    .inventory-index-list .history-list-table th,
    .inventory-index-list .history-list-table td {
      vertical-align: middle;
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }

    .inventory-index-list .inv-list-toolbar .form-control,
    .inventory-index-list .inv-list-toolbar .input-group-text {
      border-color: #ced4da;
    }

    .inventory-index-list .inv-seq {
      white-space: nowrap;
      font-variant-numeric: tabular-nums;
    }

    .inventory-index-list .inv-part {
      white-space: nowrap;
      font-family: var(--bs-font-monospace);
    }

    .inventory-index-list .inv-item-name,
    .inventory-index-list .inv-supplier {
      word-break: break-word;
      overflow-wrap: anywhere;
      text-align: left;
    }

    .inventory-index-list .inv-money {
      text-align: right;
      white-space: nowrap;
      font-variant-numeric: tabular-nums;
    }

    .inventory-index-list .inv-actions {
      text-align: center;
      white-space: nowrap;
    }

    .inventory-index-list .inv-actions-inner {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.25rem;
      min-height: 2.25rem;
    }
  </style>

  <div class="container mt-4 inventory-index-list mb-5 pb-3">
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

    {{-- Inventory list — same table rhythm as cashier Invoices --}}
    @php
      $invSort = \App\Models\Inventory::normalizeIndexSort(request('sort'));
      $invSortLabels = \App\Models\Inventory::indexSortLabels();
    @endphp

    <h3 class="mt-5 fw-bold"><i class="bi bi-box-seam text-primary"></i> Inventory List</h3>

    <div class="inv-list-toolbar d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 mb-3 p-3 bg-white border rounded shadow-sm"
      style="border-color:#4a90e2!important;">
      <div class="d-flex align-items-center gap-2 flex-nowrap min-w-0 w-100 ms-md-auto">
        <div class="input-group input-group-sm flex-grow-1 min-w-0" style="max-width: 24rem;">
          <span class="input-group-text bg-light py-0 d-none d-sm-inline-flex" aria-hidden="true"><i class="bi bi-search"></i></span>
          <input id="searchInput" name="q" type="search" class="form-control" autocomplete="off"
            placeholder="Search name, part #, supplier…" value="{{ request('q', '') }}"
            aria-label="Search inventory" inputmode="search">
        </div>
        <div class="dropdown flex-shrink-0">
          <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-expanded="false" aria-label="Filter and sort inventory list">
            {{ $invSortLabels[$invSort] ?? 'Newest' }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            @foreach($invSortLabels as $sortKey => $sortLabel)
              <li>
                <a class="dropdown-item @if($invSort === $sortKey) active fw-semibold @endif"
                  href="{{ route('cashier.inventory.index', array_merge(request()->only('q'), ['sort' => $sortKey])) }}">{{ $sortLabel }}</a>
              </li>
            @endforeach
          </ul>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" id="inventoryActivityLogBtn">
          Logs
        </button>
      </div>
    </div>

    @if($inventories->isEmpty())
      <div class="alert alert-light border shadow-sm mb-0" role="alert" style="border-color:#4a90e2!important;color:#212529;">
        @if(request()->filled('q'))
          No items match your search.
        @else
          No inventory items yet.
        @endif
      </div>
    @else
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <small class="text-muted">{{ $inventories->total() }} item(s) total</small>
      </div>

      <div class="table-responsive shadow-sm rounded border bg-white overflow-hidden">
        <table id="inventoryTable" class="table table-striped table-bordered align-middle history-list-table mb-0">
          <colgroup>
            <col style="width:4%;">
            <col style="width:22%;">
            <col style="width:9%;">
            <col style="width:5%;">
            <col style="width:6%;">
            <col style="width:8%;">
            <col style="width:8%;">
            <col style="width:18%;">
            <col style="width:12%;">
          </colgroup>
          <thead class="table-light">
            <tr>
              <th class="inv-seq">#</th>
              <th class="inv-item-name">Item Name</th>
              <th class="inv-part">Part #</th>
              <th class="inv-money">Qty</th>
              <th class="inv-money" title="Units billed on paid invoices (linked part)">Sold</th>
              <th class="inv-money">Selling (₱)</th>
              <th class="inv-money">Acquisition (₱)</th>
              <th class="inv-supplier">Supplier</th>
              <th class="inv-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($inventories as $inv)
              <tr data-id="{{ $inv->id }}"
                class="{{ $inv->quantity == 0 ? 'table-danger' : ($inv->quantity < 3 ? 'table-warning' : '') }}">
                <td class="inv-seq">{{ ($inventories->currentPage() - 1) * $inventories->perPage() + $loop->iteration }}</td>
                <td class="inv-item-name">{{ $inv->item_name }}</td>
                <td class="inv-part">{{ $inv->part_number }}</td>
                <td class="inv-money">{{ $inv->quantity }}</td>
                <td class="inv-money fw-semibold">{{ (int) ($inv->sold_qty ?? 0) }}</td>
                <td class="inv-money">{{ number_format($inv->selling, 2) }}</td>
                <td class="inv-money">{{ $inv->acquisition_price ? number_format($inv->acquisition_price, 2) : '—' }}</td>
                <td class="inv-supplier">{{ $inv->supplier ?? '—' }}</td>
                <td class="inv-actions">
                  <div class="inv-actions-inner">
                    <button type="button"
                      class="btn btn-sm btn-outline-primary inv-update-btn"
                      title="Edit, add stock, remove stock…"
                      data-id="{{ $inv->id }}"
                      data-item_name="{{ e($inv->item_name) }}"
                      data-part_number="{{ $inv->part_number }}"
                      data-quantity="{{ $inv->quantity }}"
                      data-selling="{{ $inv->selling }}"
                      data-acquisition_price="{{ $inv->acquisition_price }}"
                      data-supplier="{{ e($inv->supplier ?? '') }}"
                      data-url-add="{{ route('cashier.inventory.stock.add', $inv) }}"
                      data-url-remove="{{ route('cashier.inventory.stock.remove', $inv) }}">
                      Update
                    </button>
                    <button type="button"
                      class="btn btn-sm btn-outline-danger inventory-delete-trigger"
                      title="Delete"
                      data-delete-url="{{ route('cashier.inventory.destroy', $inv) }}">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-center my-4">
        {{ $inventories->onEachSide(1)->links() }}
      </div>
    @endif
  </div>

  {{-- Global stock activity log (add/remove movements) --}}
  <div class="modal fade" id="inventoryActivityLogModal" tabindex="-1"
    aria-labelledby="inventoryActivityLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inventoryActivityLogModalLabel">Stock activity log</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <p class="small text-muted px-3 pt-2 mb-2">Add/remove stock events across all items (newest first).</p>
          <div class="px-3 pb-3 border-bottom bg-light">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white" aria-hidden="true"><i class="bi bi-search"></i></span>
              <input type="search" id="inventoryActivityLogSearchInput" class="form-control" autocomplete="off"
                placeholder="Search date, user, part #, item, note or reason…" aria-label="Search activity log">
              <button type="button" class="btn btn-outline-secondary" id="inventoryActivityLogSearchBtn" title="Search">Search</button>
              <button type="button" class="btn btn-outline-secondary" id="inventoryActivityLogSearchClear" title="Clear search">Clear</button>
            </div>
            <small class="text-muted d-block mt-1">Use a full date <span class="font-monospace">YYYY-MM-DD</span> to match that day; text matches user, part #, item name, when, note, or reason.</small>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>When</th>
                  <th class="text-start">Item</th>
                  <th>User</th>
                  <th>Type</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Before</th>
                  <th class="text-end">After</th>
                  <th class="text-start">Note / Reason</th>
                </tr>
              </thead>
              <tbody id="inventoryActivityLogTableBody"></tbody>
            </table>
          </div>
          <p class="small text-muted px-3 py-2 mb-0 d-none" id="inventoryActivityLogEmpty">No stock movements recorded yet.</p>
        </div>
        <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <small class="text-muted mb-0" id="inventoryActivityLogPagerLabel"></small>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" id="inventoryActivityLogPrev" disabled>Previous</button>
            <button type="button" class="btn btn-outline-secondary" id="inventoryActivityLogNext" disabled>Next</button>
          </div>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
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

  {{-- Step 1: Update menu — Edit / Add / Remove (then each flow has Save/Cancel) --}}
  <div class="modal fade" id="inventoryUpdateChoicesModal" tabindex="-1"
    aria-labelledby="inventoryUpdateChoicesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-semibold" id="inventoryUpdateChoicesModalLabel">Update item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body pt-2">
          <p class="small text-muted mb-3 border-bottom pb-2" id="updateChoicesItemSubtitle"></p>
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-primary text-start py-2" id="choiceEditDetailsBtn">
              <span class="fw-semibold">Edit</span><span class="d-block small text-muted">Name, part #, prices, supplier</span>
            </button>
            <button type="button" class="btn btn-outline-success text-start py-2" id="choiceAddStockBtn">
              <span class="fw-semibold">Add stock</span><span class="d-block small text-muted">Increase quantity (logged)</span>
            </button>
            <button type="button" class="btn btn-outline-warning text-start py-2" id="choiceRemoveStockBtn">
              <span class="fw-semibold">Remove stock</span><span class="d-block small text-muted">Decrease quantity with reason (logged)</span>
            </button>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
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
          <label class="form-label">Current quantity</label>
          <div id="edit_quantity_display" class="rounded border bg-light px-3 py-2 fw-semibold user-select-all text-body-secondary" role="status" aria-live="polite">—</div>
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

  {{-- Add stock --}}
  <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="addStockModalLabel">Add stock</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2" id="addStockItemLabel"></p>
          <div class="mb-3">
            <label class="form-label">Quantity to add</label>
            <input type="number" min="1" step="1" class="form-control" id="addStockQtyInput" required>
          </div>
          <div class="mb-0">
            <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" class="form-control" id="addStockNoteInput" maxlength="1000" placeholder="e.g. Supplier delivery">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" id="confirmAddStockBtn">Add</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Remove stock --}}
  <div class="modal fade" id="removeStockModal" tabindex="-1" aria-labelledby="removeStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title" id="removeStockModalLabel">Remove stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2" id="removeStockItemLabel"></p>
          <p class="small mb-2">Available: <strong id="removeStockAvailable"></strong></p>
          <div class="mb-3">
            <label class="form-label">Quantity to remove</label>
            <input type="number" min="1" step="1" class="form-control" id="removeStockQtyInput" required>
          </div>
          <div class="mb-0">
            <label class="form-label">Reason <span class="text-danger">*</span></label>
            <textarea class="form-control" id="removeStockReasonInput" rows="2" required maxlength="1000" placeholder="Why is stock being removed?"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning" id="confirmRemoveStockBtn">Remove</button>
        </div>
      </div>
    </div>
  </div>

  {{-- AJAX + Edit + Search Scripts --}}
  <script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const stockActivityLogUrl = @json(route('cashier.inventory.stock.activity'));
    let activityLogPage = 1;
    let activityLogLastPage = 1;
    let activityLogSearchQ = '';

    function renderInventoryActivityLogPage(payload) {
      const tbody = document.getElementById('inventoryActivityLogTableBody');
      const emptyEl = document.getElementById('inventoryActivityLogEmpty');
      const pagerLabel = document.getElementById('inventoryActivityLogPagerLabel');
      const prevBtn = document.getElementById('inventoryActivityLogPrev');
      const nextBtn = document.getElementById('inventoryActivityLogNext');
      if (!tbody || !emptyEl || !pagerLabel || !prevBtn || !nextBtn) {
        return;
      }
      tbody.innerHTML = '';
      const rows = payload.data || [];
      if (!rows.length) {
        emptyEl.textContent = activityLogSearchQ
          ? 'No movements match your search.'
          : 'No stock movements recorded yet.';
        emptyEl.classList.remove('d-none');
        pagerLabel.textContent = '';
        prevBtn.disabled = true;
        nextBtn.disabled = true;
        return;
      }
      emptyEl.classList.add('d-none');
      rows.forEach(function (r) {
        const tr = document.createElement('tr');
        const typeLabel = r.direction === 'remove' ? 'Remove' : 'Add';
        var noteCell = String(r.reason || r.note || '—');
        noteCell = noteCell.replace(/</g, '&lt;');
        var uname = String(r.user_name || '—').replace(/</g, '&lt;');
        var itemName = String(r.item_name || '—').replace(/</g, '&lt;');
        var partNum = String(r.part_number != null && r.part_number !== '' ? r.part_number : '—').replace(/</g, '&lt;');
        tr.innerHTML =
          '<td class="small">' + (r.created_at || '—') + '</td>' +
          '<td class="small text-start"><div class="fw-medium">' + itemName + '</div><div class="text-muted">' + partNum + '</div></td>' +
          '<td class="small">' + uname + '</td>' +
          '<td class="small">' + typeLabel + '</td>' +
          '<td class="text-end">' + r.quantity + '</td>' +
          '<td class="text-end">' + r.quantity_before + '</td>' +
          '<td class="text-end">' + r.quantity_after + '</td>' +
          '<td class="small text-start">' + noteCell + '</td>';
        tbody.appendChild(tr);
      });
      activityLogPage = payload.current_page || 1;
      activityLogLastPage = payload.last_page || 1;
      pagerLabel.textContent = 'Page ' + activityLogPage + ' of ' + activityLogLastPage;
      prevBtn.disabled = activityLogPage <= 1;
      nextBtn.disabled = activityLogPage >= activityLogLastPage;
    }

    async function loadInventoryActivityLogPage(pageNum) {
      try {
        const url = new URL(stockActivityLogUrl, window.location.href);
        url.searchParams.set('page', String(pageNum));
        if (activityLogSearchQ) {
          url.searchParams.set('q', activityLogSearchQ);
        } else {
          url.searchParams.delete('q');
        }
        const res = await fetch(url.toString(), {
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
          alert('Could not load activity log.');
          return;
        }
        renderInventoryActivityLogPage(await res.json());
      } catch (_e) {
        alert('Network error.');
      }
    }

    (function () {
      var openBtn = document.getElementById('inventoryActivityLogBtn');
      var prevBtn = document.getElementById('inventoryActivityLogPrev');
      var nextBtn = document.getElementById('inventoryActivityLogNext');
      var modalEl = document.getElementById('inventoryActivityLogModal');
      var searchInp = document.getElementById('inventoryActivityLogSearchInput');
      var searchBtn = document.getElementById('inventoryActivityLogSearchBtn');
      var clearBtn = document.getElementById('inventoryActivityLogSearchClear');
      var debounceT;

      function syncSearchFromInputAndLoad() {
        activityLogSearchQ = searchInp ? searchInp.value.trim() : '';
        loadInventoryActivityLogPage(1);
      }

      if (openBtn && modalEl) {
        openBtn.addEventListener('click', function () {
          activityLogSearchQ = '';
          if (searchInp) searchInp.value = '';
          var tb = document.getElementById('inventoryActivityLogTableBody');
          if (tb) tb.innerHTML = '';
          var emp = document.getElementById('inventoryActivityLogEmpty');
          if (emp) emp.classList.add('d-none');
          bootstrap.Modal.getOrCreateInstance(modalEl).show();
          loadInventoryActivityLogPage(1);
        });
      }
      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          if (activityLogPage <= 1) return;
          loadInventoryActivityLogPage(activityLogPage - 1);
        });
      }
      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          if (activityLogPage >= activityLogLastPage) return;
          loadInventoryActivityLogPage(activityLogPage + 1);
        });
      }
      if (searchInp) {
        searchInp.addEventListener('input', function () {
          clearTimeout(debounceT);
          debounceT = window.setTimeout(syncSearchFromInputAndLoad, 550);
        });
        searchInp.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceT);
            syncSearchFromInputAndLoad();
          }
        });
      }
      if (searchBtn) {
        searchBtn.addEventListener('click', function () {
          clearTimeout(debounceT);
          syncSearchFromInputAndLoad();
        });
      }
      if (clearBtn && searchInp) {
        clearBtn.addEventListener('click', function () {
          clearTimeout(debounceT);
          searchInp.value = '';
          activityLogSearchQ = '';
          loadInventoryActivityLogPage(1);
        });
      }
    })();

    let pendingDeleteUrl = null;
    let pendingAddStockUrl = null;
    let pendingRemoveStockUrl = null;
    let pendingUpdateRowBtn = null;

    function afterModalHidden(modalId, callback) {
      const el = document.getElementById(modalId);
      if (!el) {
        callback();
        return;
      }
      const inst = bootstrap.Modal.getInstance(el);
      if (!inst || !el.classList.contains('show')) {
        callback();
        return;
      }
      function onHidden() {
        el.removeEventListener('hidden.bs.modal', onHidden);
        callback();
      }
      el.addEventListener('hidden.bs.modal', onHidden);
      inst.hide();
    }

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

    document.querySelectorAll('.inv-update-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pendingUpdateRowBtn = this;
        const sub = document.getElementById('updateChoicesItemSubtitle');
        if (sub) {
          sub.textContent = this.dataset.item_name ? 'Item: ' + this.dataset.item_name : '';
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('inventoryUpdateChoicesModal')).show();
      });
    });

    document.getElementById('choiceEditDetailsBtn').addEventListener('click', function () {
      const row = pendingUpdateRowBtn;
      if (!row) return;
      afterModalHidden('inventoryUpdateChoicesModal', function () {
        const ds = row.dataset;
        document.getElementById('edit_inventory_id').value = ds.id;
        document.getElementById('edit_item_name').value = ds.item_name || '';
        document.getElementById('edit_part_number').value = ds.part_number || '';
        var qDisp = document.getElementById('edit_quantity_display');
        var qs = ds.quantity !== undefined && ds.quantity !== null && String(ds.quantity) !== '' ? String(ds.quantity) : '—';
        qDisp.textContent = qs;
        qDisp.classList.toggle('text-danger', qs !== '—');
        qDisp.classList.toggle('text-body-secondary', qs === '—');
        document.getElementById('edit_selling').value = ds.selling || '';
        document.getElementById('edit_acquisition_price').value = ds.acquisition_price || '';
        document.getElementById('edit_supplier').value = ds.supplier || '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editInventoryModal')).show();
      });
    });

    document.getElementById('choiceAddStockBtn').addEventListener('click', function () {
      const row = pendingUpdateRowBtn;
      if (!row) return;
      afterModalHidden('inventoryUpdateChoicesModal', function () {
        pendingAddStockUrl = row.dataset.urlAdd;
        document.getElementById('addStockItemLabel').textContent = row.dataset.item_name || '';
        document.getElementById('addStockQtyInput').value = '';
        document.getElementById('addStockNoteInput').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addStockModal')).show();
      });
    });

    document.getElementById('choiceRemoveStockBtn').addEventListener('click', function () {
      const row = pendingUpdateRowBtn;
      if (!row) return;
      afterModalHidden('inventoryUpdateChoicesModal', function () {
        pendingRemoveStockUrl = row.dataset.urlRemove;
        const avail = parseInt(row.dataset.quantity, 10) || 0;
        document.getElementById('removeStockItemLabel').textContent = row.dataset.item_name || '';
        document.getElementById('removeStockAvailable').textContent = String(avail);
        document.getElementById('removeStockQtyInput').value = '';
        document.getElementById('removeStockQtyInput').max = avail > 0 ? avail : 0;
        document.getElementById('removeStockReasonInput').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('removeStockModal')).show();
      });
    });

    document.getElementById('confirmAddStockBtn').addEventListener('click', async function () {
      const url = pendingAddStockUrl;
      if (!url) return;
      const qty = parseInt(document.getElementById('addStockQtyInput').value, 10);
      const note = document.getElementById('addStockNoteInput').value.trim();
      if (!qty || qty < 1) {
        alert('Enter a valid quantity (1 or more).');
        return;
      }
      try {
        const res = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ quantity: qty, note: note || null }),
        });
        if (res.ok) {
          pendingAddStockUrl = null;
          bootstrap.Modal.getInstance(document.getElementById('addStockModal'))?.hide();
          showInvToastGreen('Stock added');
        } else if (res.status === 422) {
          const j = await res.json().catch(() => ({}));
          alert(j.message || 'Validation failed.');
        } else {
          alert('Could not add stock.');
        }
      } catch (_e) {
        alert('Network error.');
      }
    });

    document.getElementById('confirmRemoveStockBtn').addEventListener('click', async function () {
      const url = pendingRemoveStockUrl;
      if (!url) return;
      const qty = parseInt(document.getElementById('removeStockQtyInput').value, 10);
      const reason = document.getElementById('removeStockReasonInput').value.trim();
      if (!qty || qty < 1) {
        alert('Enter a valid quantity (1 or more).');
        return;
      }
      if (!reason) {
        alert('Reason is required when removing stock.');
        return;
      }
      try {
        const res = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ quantity: qty, reason }),
        });
        if (res.ok) {
          pendingRemoveStockUrl = null;
          bootstrap.Modal.getInstance(document.getElementById('removeStockModal'))?.hide();
          showInvToastGreen('Stock removed');
        } else if (res.status === 422) {
          const j = await res.json().catch(() => ({}));
          const msg = j.errors && j.errors.quantity ? j.errors.quantity.join(' ') : (j.message || 'Validation failed.');
          alert(msg);
        } else {
          alert('Could not remove stock.');
        }
      } catch (_e) {
        alert('Network error.');
      }
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


    document.getElementById('saveEditBtn').addEventListener('click', async () => {
    const id = document.getElementById('edit_inventory_id').value;
    const data = {
      item_name: document.getElementById('edit_item_name').value,
      part_number: document.getElementById('edit_part_number').value,
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteInventoryModal')).show();
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