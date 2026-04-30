<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryStockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    private const PER_PAGE = 25;

    private const STOCK_MOVEMENTS_PAGE = 30;

    public function index(Request $request)
    {
        $inventories = Inventory::paginateForIndex($request, self::PER_PAGE);

        return view('cashier.inventory', compact('inventories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'selling' => 'required|numeric|min:0',
            'acquisition_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
        ]);

        $inv = Inventory::create($data);

        $uid = Auth::id();
        if ($uid && (int) $inv->quantity > 0) {
            InventoryStockMovement::query()->create([
                'inventory_id' => $inv->id,
                'user_id' => $uid,
                'direction' => InventoryStockMovement::DIRECTION_ADD,
                'quantity' => (int) $inv->quantity,
                'quantity_before' => 0,
                'quantity_after' => (int) $inv->quantity,
                'reason' => null,
                'note' => 'Initial stock',
                'created_at' => now(),
            ]);
        }

        return response()->json($inv);
    }

    /**
     * Update item metadata only. Quantity changes must use stock-add / stock-remove (logged).
     */
    public function update(Request $request, Inventory $inventory)
    {
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'selling' => 'required|numeric|min:0',
            'acquisition_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
        ]);

        $inventory->update($data);

        return response()->json(
            $inventory,
            200,
            [],
            JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function addStock(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        $qty = (int) $validated['quantity'];
        $uid = Auth::id();
        if (! $uid) {
            abort(401);
        }

        $movement = DB::transaction(function () use ($inventory, $qty, $uid, $validated) {
            $row = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $before = (int) $row->quantity;
            $after = $before + $qty;
            $row->quantity = $after;
            $row->save();

            return InventoryStockMovement::query()->create([
                'inventory_id' => $row->id,
                'user_id' => $uid,
                'direction' => InventoryStockMovement::DIRECTION_ADD,
                'quantity' => $qty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => null,
                'note' => $validated['note'] ?? null,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'inventory' => $inventory->fresh(),
            'movement' => $movement,
        ]);
    }

    public function removeStock(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
        ]);

        $qty = (int) $validated['quantity'];
        $uid = Auth::id();
        if (! $uid) {
            abort(401);
        }

        $movement = DB::transaction(function () use ($inventory, $qty, $uid, $validated) {
            $row = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            $before = (int) $row->quantity;
            if ($qty > $before) {
                throw ValidationException::withMessages([
                    'quantity' => ['Cannot remove '.$qty.'. Only '.$before.' available.'],
                ]);
            }
            $after = $before - $qty;
            $row->quantity = $after;
            $row->save();

            return InventoryStockMovement::query()->create([
                'inventory_id' => $row->id,
                'user_id' => $uid,
                'direction' => InventoryStockMovement::DIRECTION_REMOVE,
                'quantity' => $qty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $validated['reason'],
                'note' => null,
                'created_at' => now(),
            ]);
        });

        return response()->json([
            'inventory' => $inventory->fresh(),
            'movement' => $movement,
        ]);
    }

    public function stockMovements(Request $request, Inventory $inventory)
    {
        $rows = InventoryStockMovement::query()
            ->where('inventory_id', $inventory->id)
            ->with(['user:id,name,email,role'])
            ->orderByDesc('created_at')
            ->paginate(self::STOCK_MOVEMENTS_PAGE);

        $data = $rows->through(function (InventoryStockMovement $m) {
            $name = $m->user ? $m->user->attributionName() : '—';

            return [
                'id' => $m->id,
                'direction' => $m->direction,
                'quantity' => $m->quantity,
                'quantity_before' => $m->quantity_before,
                'quantity_after' => $m->quantity_after,
                'reason' => $m->reason,
                'note' => $m->note,
                'user_name' => $name,
                'created_at' => $m->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json($data);
    }

    /**
     * Paginated stock add/remove activity across all inventory (cashier audit log).
     * Query `q`: matches date (when full Y-m-d given), user name/email, item name, part #, note, reason, or datetime substring.
     */
    public function allStockMovements(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim((string) $request->get('q', ''));

        $query = InventoryStockMovement::query()
            ->with([
                'user:id,name,email,role',
                'inventory:id,item_name,part_number',
            ])
            ->orderByDesc('created_at');

        $this->applyStockActivitySearch($query, $search);

        $rows = $query->paginate(self::STOCK_MOVEMENTS_PAGE)->withQueryString();

        $data = $rows->through(function (InventoryStockMovement $m) {
            $name = $m->user ? $m->user->attributionName() : '—';
            $inv = $m->inventory;

            return [
                'id' => $m->id,
                'direction' => $m->direction,
                'quantity' => $m->quantity,
                'quantity_before' => $m->quantity_before,
                'quantity_after' => $m->quantity_after,
                'reason' => $m->reason,
                'note' => $m->note,
                'user_name' => $name,
                'item_name' => $inv?->item_name,
                'part_number' => $inv?->part_number,
                'created_at' => $m->created_at?->format('Y-m-d H:i'),
            ];
        });

        return response()->json($data);
    }

    /**
     * @param  Builder<InventoryStockMovement>  $query
     */
    private function applyStockActivitySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $tbl = $query->getModel()->getTable();

        $query->where(function (Builder $outer) use ($like, $search, $tbl) {
            $outer->whereHas('inventory', static function (Builder $inv) use ($like) {
                $inv->where('item_name', 'like', $like)
                    ->orWhere('part_number', 'like', $like);
            });

            $outer->orWhereHas('user', static function (Builder $u) use ($like) {
                $u->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });

            $outer->orWhere($tbl.'.note', 'like', $like)
                ->orWhere($tbl.'.reason', 'like', $like);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                $outer->orWhereDate($tbl.'.created_at', $search);
            }

            $outer->orWhere($tbl.'.created_at', 'like', $like);
        });
    }

    public function destroy(Request $request, Inventory $inventory)
    {
        $inventory->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()->route(
            'cashier.inventory.index',
            array_filter([
                'q' => $request->query('q'),
                'sort' => $request->query('sort'),
            ])
        );
    }
}
