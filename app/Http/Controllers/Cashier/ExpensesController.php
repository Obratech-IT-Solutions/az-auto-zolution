<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Support\CashierListLimits;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();

        $title = trim((string) $request->get('title', ''));
        if ($title !== '') {
            $like = '%' . addcslashes($title, '%_\\') . '%';
            $query->where('title', 'like', $like);
        }

        $amountRaw = $request->input('amount');
        if ($amountRaw !== null && $amountRaw !== '') {
            if (is_numeric($amountRaw)) {
                $query->where('amount', '=', round((float) $amountRaw, 2));
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $expenses = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(CashierListLimits::EXPENSES_PER_PAGE)
            ->withQueryString();

        return view('cashier.expenses', compact('expenses'));
    }

    public function create()
    {
        return view('cashier.expenses-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        Expense::create($validated);
        return redirect()->route('cashier.expenses.index')->with('success', 'Expense added!');
    }

    public function edit($id)
    {
        $expense = Expense::findOrFail($id);
        return view('cashier.expenses-edit', compact('expense'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);
        $expense = Expense::findOrFail($id);
        $expense->update($validated);

        return redirect()->route('cashier.expenses.index')->with('success', 'Expense updated!');
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();
        return redirect()->route('cashier.expenses.index')->with('success', 'Expense deleted!');
    }
}
