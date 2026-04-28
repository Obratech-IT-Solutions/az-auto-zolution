<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\ArCollection;
use App\Models\CashDeposit;
use Illuminate\Http\Request;

class ARCashDepositController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $arQ = trim((string) $request->get('ar_q', ''));
        $cdQ = trim((string) $request->get('cd_q', ''));

        $arQuery = ArCollection::query()
            ->select(['id', 'date', 'description', 'amount'])
            ->orderByDesc('date');
        if ($arQ !== '') {
            $like = '%' . addcslashes($arQ, '%_\\') . '%';
            $arQuery->where('description', 'like', $like);
        }
        $arCollections = $arQuery
            ->paginate(self::PER_PAGE, ['id', 'date', 'description', 'amount'], 'ar_page')
            ->withQueryString();

        $cdQuery = CashDeposit::query()
            ->select(['id', 'date', 'description', 'amount'])
            ->orderByDesc('date');
        if ($cdQ !== '') {
            $like = '%' . addcslashes($cdQ, '%_\\') . '%';
            $cdQuery->where('description', 'like', $like);
        }
        $cashDeposits = $cdQuery
            ->paginate(self::PER_PAGE, ['id', 'date', 'description', 'amount'], 'cd_page')
            ->withQueryString();

        return view('cashier.ar-cashdeposit', compact('arCollections', 'cashDeposits'));
    }

    public function storeAR(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
        $validated['user_id'] = auth()->id();
        ArCollection::create($validated);

        return back()->with('success', 'A/R Collection recorded!');
    }

    public function updateAR(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
        $ar = ArCollection::findOrFail($id);
        $ar->update($validated);

        return back()->with('success', 'A/R Collection updated!');
    }

    public function storeCashDeposit(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
        ]);
        $validated['user_id'] = auth()->id();
        CashDeposit::create($validated);

        return back()->with('success', 'Cash Deposit recorded!');
    }

    public function updateCashDeposit(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
        $deposit = CashDeposit::findOrFail($id);
        $deposit->update($validated);

        return back()->with('success', 'Cash Deposit updated!');
    }

    public function destroyAR($id)
    {
        $ar = ArCollection::findOrFail($id);
        $ar->delete();

        return back()->with('success', 'A/R Collection deleted!');
    }

    public function destroyCashDeposit($id)
    {
        $deposit = CashDeposit::findOrFail($id);
        $deposit->delete();

        return back()->with('success', 'Cash Deposit deleted!');
    }
}
