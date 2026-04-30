@extends('layouts.cashier')
@section('title', 'A/R Collection & Cash Deposit')

@section('content')
@include('cashier.partials.cashier-flash-toast')
<div class="container-fluid mt-2">
    <div class="row g-4">
        <!-- A/R Collection Section -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="fas fa-money-bill-wave me-2"></i> A/R Collection</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="search" id="arSearchInput" class="form-control form-control-sm" placeholder="Search description…"
                            value="{{ request('ar_q', '') }}" autocomplete="off" style="min-width: 160px;">
                        <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addARModal">
                            <i class="fas fa-plus"></i> Add A/R
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Add AR Modal -->
                    <div class="modal fade" id="addARModal" tabindex="-1" aria-labelledby="addARModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('cashier.ar-cashdeposit.storeAR') }}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="addARModalLabel">Add A/R Collection</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" name="description" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount (₱)</label>
                                            <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-primary" type="submit">Save</button>
                                        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center" style="width:115px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($arCollections as $ar)
                                    <tr>
                                        <td class="text-nowrap">{{ $ar->date->format('M d, Y') }}</td>
                                        <td>{{ $ar->description }}</td>
                                        <td class="text-end">₱{{ number_format($ar->amount, 2) }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editARModal{{ $ar->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm ar-deposit-delete-btn"
                                                title="Delete" data-bs-toggle="modal"
                                                data-bs-target="#arCashDepositDeleteModal"
                                                data-delete-action="{{ route('cashier.ar-cashdeposit.destroyAR', $ar->id) }}"
                                                data-item-label="this A/R collection"><i class="fas fa-trash"></i></button>
                                            <div class="modal fade" id="editARModal{{ $ar->id }}" tabindex="-1" aria-labelledby="editARModalLabel{{ $ar->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form method="POST" action="{{ route('cashier.ar-cashdeposit.updateAR', $ar->id) }}">
                                                        @csrf @method('PUT')
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title" id="editARModalLabel{{ $ar->id }}">Edit A/R</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Date</label>
                                                                    <input type="date" name="date" class="form-control" value="{{ $ar->date->format('Y-m-d') }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description</label>
                                                                    <input type="text" name="description" class="form-control" value="{{ $ar->description }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Amount (₱)</label>
                                                                    <input type="number" name="amount" class="form-control" value="{{ $ar->amount }}" min="0" step="0.01" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button class="btn btn-warning" type="submit">Update</button>
                                                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            @if(request()->filled('ar_q'))
                                                No A/R rows match your search.
                                            @else
                                                No A/R collections recorded.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($arCollections->total() > 0)
                <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">{{ $arCollections->total() }} row(s) total</small>
                    {{ $arCollections->onEachSide(1)->links() }}
                </div>
                @endif
            </div>
        </div>
        <!-- Cash Deposit Section -->
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span><i class="fas fa-piggy-bank me-2"></i> Cash Deposit</span>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="search" id="cdSearchInput" class="form-control form-control-sm" placeholder="Search description…"
                            value="{{ request('cd_q', '') }}" autocomplete="off" style="min-width: 160px;">
                        <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addCashDepositModal">
                            <i class="fas fa-plus"></i> Add Deposit
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Add Cash Deposit Modal -->
                    <div class="modal fade" id="addCashDepositModal" tabindex="-1" aria-labelledby="addCashDepositModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('cashier.ar-cashdeposit.storeCashDeposit') }}">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title" id="addCashDepositModalLabel">Add Cash Deposit</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" name="description" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount (₱)</label>
                                            <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-success" type="submit">Save</button>
                                        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center" style="width:115px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashDeposits as $cd)
                                    <tr>
                                        <td class="text-nowrap">{{ $cd->date->format('M d, Y') }}</td>
                                        <td>{{ $cd->description }}</td>
                                        <td class="text-end">₱{{ number_format($cd->amount, 2) }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCashDepositModal{{ $cd->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm ar-deposit-delete-btn"
                                                title="Delete" data-bs-toggle="modal"
                                                data-bs-target="#arCashDepositDeleteModal"
                                                data-delete-action="{{ route('cashier.ar-cashdeposit.destroyCashDeposit', $cd->id) }}"
                                                data-item-label="this cash deposit"><i class="fas fa-trash"></i></button>
                                            <div class="modal fade" id="editCashDepositModal{{ $cd->id }}" tabindex="-1" aria-labelledby="editCashDepositModalLabel{{ $cd->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <form method="POST" action="{{ route('cashier.ar-cashdeposit.updateCashDeposit', $cd->id) }}">
                                                        @csrf @method('PUT')
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-warning">
                                                                <h5 class="modal-title" id="editCashDepositModalLabel{{ $cd->id }}">Edit Cash Deposit</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Date</label>
                                                                    <input type="date" name="date" class="form-control" value="{{ $cd->date->format('Y-m-d') }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Description</label>
                                                                    <input type="text" name="description" class="form-control" value="{{ $cd->description }}" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Amount (₱)</label>
                                                                    <input type="number" name="amount" class="form-control" value="{{ $cd->amount }}" min="0" step="0.01" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button class="btn btn-warning" type="submit">Update</button>
                                                                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            @if(request()->filled('cd_q'))
                                                No cash deposits match your search.
                                            @else
                                                No cash deposits recorded.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($cashDeposits->total() > 0)
                <div class="card-footer py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">{{ $cashDeposits->total() }} row(s) total</small>
                    {{ $cashDeposits->onEachSide(1)->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<form id="arCashDepositDeleteForm" method="POST" class="d-none" action="">@csrf @method('DELETE')</form>

<div class="modal fade" id="arCashDepositDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger overflow-hidden shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="arCashDepositDeleteModalMsg">Remove this entry? This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="arCashDepositDeleteConfirmBtn">
                    <i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('arCashDepositDeleteModal');
    var formEl = document.getElementById('arCashDepositDeleteForm');
    var msgEl = document.getElementById('arCashDepositDeleteModalMsg');
    if (modalEl && formEl) {
      modalEl.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        formEl.setAttribute('action', btn.getAttribute('data-delete-action') || '');
        var label = btn.getAttribute('data-item-label') || 'this record';
        if (msgEl) {
          msgEl.textContent = 'Permanently remove ' + label + '? This cannot be undone.';
        }
      });
      var confirmBtn = document.getElementById('arCashDepositDeleteConfirmBtn');
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
(function () {
    function wireSearch(inputId, param) {
        const el = document.getElementById(inputId);
        if (!el) return;
        let t;
        el.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => {
                const url = new URL(window.location.href);
                const v = el.value.trim();
                if (v) {
                    url.searchParams.set(param, v);
                } else {
                    url.searchParams.delete(param);
                }
                if (param === 'ar_q') {
                    url.searchParams.delete('ar_page');
                } else {
                    url.searchParams.delete('cd_page');
                }
                window.location.assign(url.toString());
            }, 400);
        });
    }
    wireSearch('arSearchInput', 'ar_q');
    wireSearch('cdSearchInput', 'cd_q');
})();
</script>
@endsection
