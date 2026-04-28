<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;    // ← use the Inventory model

class InventoryController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $inventories = Inventory::paginateForIndex($request, self::PER_PAGE);

        return view('admin.inventory', compact('inventories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name'         => 'required|string|max:255',
            'part_number'       => 'required|string|max:255',
            'quantity'          => 'required|integer|min:0',
            'selling'           => 'required|numeric|min:0',
            'acquisition_price' => 'nullable|numeric|min:0',
            'supplier'          => 'nullable|string|max:255',
        ]);

        $inv = Inventory::create($data);
        return response()->json($inv);
    }

    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'item_name'         => 'required|string|max:255',
            'part_number'       => 'required|string|max:255',
            'quantity'          => 'required|integer|min:0',
            'selling'           => 'required|numeric|min:0',
            'acquisition_price' => 'nullable|numeric|min:0',
            'supplier'          => 'nullable|string|max:255',
        ]);

        $inventory->update($data);
        return response()->json($inventory);
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return response()->json(['deleted' => true]);
    }
}
