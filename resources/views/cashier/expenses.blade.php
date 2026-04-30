@extends('layouts.cashier')
@section('title', 'Expenses')

@section('content')
@include('cashier.partials.cashier-flash-toast')
<style>
    /* One table per day: same column widths so sections line up vertically */
    .expenses-list-table {
        table-layout: fixed;
        width: 100%;
    }
    .expenses-list-table th,
    .expenses-list-table td {
        vertical-align: middle;
    }
    .expenses-list-table .exp-title {
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .expenses-list-table .exp-amount {
        text-align: right;
        white-space: nowrap;
    }
    .expenses-list-table .exp-actions {
        text-align: end;
        white-space: nowrap;
    }
    .expenses-list-table .exp-actions .btn {
        padding: 0.25rem 0.45rem;
    }
</style>
<div class="container">
    <div class="d-flex flex-wrap align-items-end gap-2 gap-md-3 mb-3 mt-2">
        <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="fas fa-plus"></i> Add Expense
        </button>

        <form method="GET" action="{{ route('cashier.expenses.index') }}" class="row g-2 flex-grow-1 align-items-end ms-md-2" style="min-width: 220px;">
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <label for="filter_title" class="form-label small text-muted mb-0">Title</label>
                <input type="text" name="title" id="filter_title" class="form-control form-control-sm"
                       placeholder="Search title…" value="{{ request('title', '') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                <label for="filter_amount" class="form-label small text-muted mb-0">Amount (₱)</label>
                <input type="number" name="amount" id="filter_amount" class="form-control form-control-sm"
                       placeholder="Exact" value="{{ request('amount', '') }}" min="0" step="0.01">
            </div>
            <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                <label for="filter_date" class="form-label small text-muted mb-0">Date</label>
                <input type="date" name="date" id="filter_date" class="form-control form-control-sm"
                       value="{{ request('date', '') }}">
            </div>
            <div class="col-12 col-sm-6 col-md-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Search
                </button>
                @if(request()->filled('title') || request()->filled('amount') || request()->filled('date'))
                    <a href="{{ route('cashier.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" action="{{ route('cashier.expenses.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addExpenseModalLabel">Add Expense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expense_date" class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" id="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="expense_title" class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" id="expense_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="expense_amount" class="form-label fw-semibold">Amount (₱)</label>
                        <input type="number" name="amount" id="expense_amount" class="form-control" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
      </div>
    </div>

    <!-- History Table -->
    <div class="table-responsive bg-white shadow-sm rounded p-2">
        @php
            $grouped = $expenses->getCollection()->groupBy(function($e) { return \Carbon\Carbon::parse($e->date)->format('F d, Y'); });
        @endphp
        @forelse($grouped as $date => $expenseList)
            <h5 class="mt-4 mb-2 text-primary">{{ $date }}</h5>
            <table class="table table-bordered align-middle mb-3 expenses-list-table">
                <colgroup>
                    <col style="width: 58%;">
                    <col style="width: 22%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead class="table-light">
                    <tr>
                        <th class="exp-title">Title</th>
                        <th class="exp-amount">Amount</th>
                        <th class="exp-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenseList as $expense)
                        <tr>
                            <td class="exp-title">{{ $expense->title }}</td>
                            <td class="exp-amount">₱{{ number_format($expense->amount, 2) }}</td>
                            <td class="exp-actions">
                                <!-- Edit (Modal) -->
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editExpenseModal{{ $expense->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Delete -->
                                <button type="button" class="btn btn-danger btn-sm expense-delete-btn"
                                    title="Delete" data-bs-toggle="modal" data-bs-target="#expenseDeleteModal"
                                    data-delete-action="{{ route('cashier.expenses.destroy', $expense->id) }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editExpenseModal{{ $expense->id }}" tabindex="-1" aria-labelledby="editExpenseModalLabel{{ $expense->id }}" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <form method="POST" action="{{ route('cashier.expenses.update', $expense->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title" id="editExpenseModalLabel{{ $expense->id }}">Edit Expense</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Date</label>
                                                    <input type="date" name="date" class="form-control" value="{{ $expense->date }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" name="title" class="form-control" value="{{ $expense->title }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Amount (₱)</label>
                                                    <input type="number" name="amount" class="form-control" value="{{ $expense->amount }}" min="0" step="0.01" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-warning">Update</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                  </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <div class="alert alert-info mt-4">
                @if(request()->filled('title') || request()->filled('amount') || request()->filled('date'))
                    No expenses match your search. Try different title, amount, or date.
                @else
                    No expenses recorded.
                @endif
            </div>
        @endforelse

        @if($expenses->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>

<form id="expenseDeleteForm" method="POST" class="d-none" action="">@csrf @method('DELETE')</form>

<div class="modal fade" id="expenseDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger overflow-hidden shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Permanently remove this expense? This cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="expenseDeleteConfirmBtn">
                    <i class="fas fa-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('expenseDeleteModal');
    var formEl = document.getElementById('expenseDeleteForm');
    if (modalEl && formEl) {
      modalEl.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) return;
        formEl.setAttribute('action', btn.getAttribute('data-delete-action') || '');
      });
      var confirmBtn = document.getElementById('expenseDeleteConfirmBtn');
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
</script>
@endsection
