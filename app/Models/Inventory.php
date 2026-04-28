<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    protected $fillable = [
        'item_name',
        'quantity',
        'part_number',
        'acquisition_price',
        'supplier',
        'selling',
    ];

    /**
     * Deduct quantity from inventory.
     * If not enough stock, deducts to zero.
     */
    public function deductQuantity($amount)
    {
        $amount = max(0, (int) $amount); // avoid negative deduction
        $this->quantity = max(0, $this->quantity - $amount);
        $this->save();
        return true;
    }

    /**
     * Paginated list for cashier/admin inventory screens (bounded page size, selective columns).
     */
    public static function paginateForIndex(Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $q = trim((string) $request->get('q', ''));
        $query = static::query()
            ->select([
                'id',
                'item_name',
                'quantity',
                'part_number',
                'acquisition_price',
                'supplier',
                'selling',
                'created_at',
            ])
            ->orderByDesc('created_at');

        static::applySearchFilter($query, $q);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Line-item picker query: joined with total quantity sold on invoice line items
     * (all document types that use invoice_items: invoicing, quotation, service_order, etc.).
     * Most-used parts sort first, then name.
     */
    public static function queryForPartPicker(): Builder
    {
        $usage = DB::table('invoice_items')
            ->whereNotNull('part_id')
            ->select('part_id', DB::raw('SUM(invoice_items.quantity) as usage_sum'))
            ->groupBy('part_id');

        return static::query()
            ->from('inventories')
            ->leftJoinSub($usage, 'inv_usage', function ($join) {
                $join->on('inv_usage.part_id', '=', 'inventories.id');
            })
            ->select([
                'inventories.id',
                'inventories.item_name',
                'inventories.part_number',
                'inventories.quantity',
                'inventories.selling',
                'inventories.acquisition_price',
            ])
            ->addSelect(DB::raw('COALESCE(inv_usage.usage_sum, 0) as usage_sum'))
            ->orderByDesc(DB::raw('COALESCE(inv_usage.usage_sum, 0)'))
            ->orderBy('inventories.item_name')
            ->orderBy('inventories.id');
    }

    /** @deprecated Prefer {@see partPickerAjaxResponse()} (paginated); avoid loading entire stock in HTML. */
    public static function selectForLineItems()
    {
        return static::queryForPartPicker()->get();
    }

    /**
     * Paginated Select2 JSON for cashier part pickers (invoice, quotation).
     */
    public static function partPickerAjaxResponse(Request $request, int $perPage = 40): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));

        $query = static::queryForPartPicker();

        static::applySearchFilter($query, $q);

        $total = (clone $query)->count();
        $rows = (clone $query)
            ->forPage($page, $perPage)
            ->get();

        $results = $rows->map(function ($p) {
            $num = $p->part_number !== null && (string) $p->part_number !== '' ? $p->part_number : 'N/A';
            $qty = (int) $p->quantity;
            $sold = (int) ($p->usage_sum ?? 0);

            return [
                'id' => $p->id,
                'text' => "[{$num}] {$p->item_name} · Stk: {$qty} · Sold: {$sold}",
                'price' => (float) $p->selling,
                'acquisition' => (float) $p->acquisition_price,
                'disabled' => $qty === 0,
                'usage_sum' => $sold,
                'part_number' => $num,
                'item_name' => $p->item_name,
                'stock' => $qty,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }

    /**
     * Map of part id => Select2 option payload for edit forms (pre-selected rows only).
     *
     * @param  iterable<int|null>  $inventoryIds
     * @return array<string, array<string, mixed>>
     */
    public static function partPickerPrefillByIds(iterable $inventoryIds): array
    {
        $ids = collect($inventoryIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return static::queryForPartPicker()
            ->whereIn('inventories.id', $ids->all())
            ->get()
            ->mapWithKeys(function ($p) {
                $key = (string) $p->id;
                $num = $p->part_number !== null && (string) $p->part_number !== '' ? $p->part_number : 'N/A';
                $qty = (int) $p->quantity;
                $sold = (int) ($p->usage_sum ?? 0);

                return [$key => [
                    'id' => (int) $p->id,
                    'text' => "[{$num}] {$p->item_name} · Stk: {$qty} · Sold: {$sold}",
                    'price' => (float) $p->selling,
                    'acquisition' => (float) $p->acquisition_price,
                    'disabled' => $qty === 0,
                    'usage_sum' => $sold,
                    'part_number' => $num,
                    'item_name' => $p->item_name,
                    'stock' => $qty,
                ]];
            })
            ->all();
    }

    /**
     * Filter inventory/part-picker queries by cashier search bar.
     * Normalizes input, applies case-insensitive substring match per field, optional exact id match,
     * and requires every whitespace-separated token to match somewhere (item name, part #, supplier, or id).
     */
    public static function applySearchFilter(Builder $query, string $q): void
    {
        $q = trim(preg_replace('/\s+/u', ' ', $q));
        if ($q === '') {
            return;
        }

        $tokens = array_values(array_filter(
            array_map('trim', preg_split('/\s+/u', $q)),
            static fn ($s): bool => $s !== ''
        ));
        if ($tokens === []) {
            return;
        }

        $t = optional($query->getModel())->getTable() ?: 'inventories';

        $query->where(function (Builder $outer) use ($tokens, $t) {
            foreach ($tokens as $rawTok) {
                $outer->where(function (Builder $inner) use ($rawTok, $t) {
                    $needle = mb_strtolower($rawTok, 'UTF-8');
                    $pattern = '%' . addcslashes($needle, '%_\\') . '%';

                    $inner->where(function (Builder $w) use ($pattern, $t, $rawTok) {
                        $w->whereRaw('LOWER(TRIM(`' . $t . '`.`item_name`)) LIKE ?', [$pattern])
                            ->orWhereRaw(
                                'LOWER(TRIM(CAST(`' . $t . '`.`part_number` AS CHAR))) LIKE ?',
                                [$pattern]
                            )
                            ->orWhereRaw(
                                'LOWER(TRIM(COALESCE(`' . $t . '`.`supplier`, \'\'))) LIKE ?',
                                [$pattern]
                            );

                        if ($rawTok !== '' && ctype_digit($rawTok)) {
                            $digits = ltrim($rawTok, '0');
                            $id = ($digits !== '' ? (int) $digits : 0)
                                ?: (($rawTok === '0') ? 0 : 0);
                            if ($rawTok === '0') {
                                $id = (int) $rawTok;
                            } else {
                                $id = (int) $rawTok;
                            }
                            if ($id > 0) {
                                $w->orWhere($t . '.id', $id);
                            }
                            if ($rawTok === '0') {
                                $w->orWhere($t . '.id', 0);
                            }
                        }
                    });
                });
            }
        });
    }
}
