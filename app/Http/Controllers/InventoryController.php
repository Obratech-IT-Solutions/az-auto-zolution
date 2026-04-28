<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $inventories = Inventory::paginateForIndex($request, self::PER_PAGE);

        return view('cashier.inventory', compact('inventories'));
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
        $logPath = base_path('.cursor/debug-c4fe64.log');
        // #region agent log
        file_put_contents(
            $logPath,
            json_encode([
                'sessionId' => 'c4fe64',
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'InventoryController::update',
                'message' => 'update entry',
                'data' => ['inventoryId' => $inventory->id],
                'hypothesisId' => 'H_update_ctrl',
            ]) . "\n",
            FILE_APPEND | LOCK_EX
        );
        // #endregion
        try {
            $data = $request->validate([
                'item_name'         => 'required|string|max:255',
                'part_number'       => 'required|string|max:255',
                'quantity'          => 'required|integer|min:0',
                'selling'           => 'required|numeric|min:0',
                'acquisition_price' => 'nullable|numeric|min:0',
                'supplier'          => 'nullable|string|max:255',
            ]);
            $inventory->update($data);
        } catch (\Throwable $e) {
            // #region agent log
            file_put_contents(
                $logPath,
                json_encode([
                    'sessionId' => 'c4fe64',
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'location' => 'InventoryController::update',
                    'message' => 'update exception',
                    'data' => [
                        'class' => \get_class($e),
                        'message' => $e->getMessage(),
                    ],
                    'hypothesisId' => 'H_update_err',
                ]) . "\n",
                FILE_APPEND | LOCK_EX
            );
            // #endregion
            throw $e;
        }

        return response()->json(
            $inventory,
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function destroy(Request $request, Inventory $inventory)
    {
        // #region agent log
        $logPath = base_path('.cursor/debug-c4fe64.log');
        file_put_contents(
            $logPath,
            json_encode([
                'sessionId' => 'c4fe64',
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'InventoryController::destroy',
                'message' => 'destroy entry',
                'data' => [
                    'inventoryId' => $inventory->id,
                    'expectsJson' => $request->expectsJson(),
                    'wantsJson' => $request->wantsJson(),
                    'accept' => $request->header('Accept'),
                    'xRequestedWith' => $request->header('X-Requested-With'),
                ],
                'hypothesisId' => 'H1',
            ]) . "\n",
            FILE_APPEND | LOCK_EX
        );
        // #endregion
        $inventory->delete();

        $returnsJson = $request->wantsJson();
        // #region agent log
        file_put_contents(
            $logPath,
            json_encode([
                'sessionId' => 'c4fe64',
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'InventoryController::destroy',
                'message' => 'destroy response path',
                'data' => ['returns' => $returnsJson ? 'json' : 'redirect'],
                'hypothesisId' => 'H1',
            ]) . "\n",
            FILE_APPEND | LOCK_EX
        );
        // #endregion

        if ($returnsJson) {
            return response()->json(['deleted' => true]);
        }

        return redirect()->route(
            'cashier.inventory.index',
            array_filter(['q' => $request->query('q')])
        );
    }
}
