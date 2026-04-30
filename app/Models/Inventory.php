<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
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

    public function stockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'inventory_id')->orderByDesc('created_at');
    }

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
     * Subquery: total units invoiced per inventory row (only inventory-linked lines on paid invoices).
     */
    public static function invoiceSoldQuantitySubquery(): QueryBuilder
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'paid')
            ->whereNotNull('invoice_items.part_id')
            ->select('invoice_items.part_id', DB::raw('SUM(invoice_items.quantity) as sold_qty'))
            ->groupBy('invoice_items.part_id');
    }

    /**
     * Normalize `sort` query for inventory index.
     * Omitting `sort` defaults to most sold (`sold_desc`). Use `sort=newest` or `recent` for newest-first.
     */
    public static function normalizeIndexSort(?string $sort): string
    {
        $sort = $sort === null ? '' : trim($sort);

        return match ($sort) {
            'oldest' => 'oldest',
            'sold', 'sold_desc', 'most_sold' => 'sold_desc',
            'sold_asc', 'least_sold' => 'sold_asc',
            'price_asc', 'cheapest' => 'price_asc',
            'price_desc', 'expensive' => 'price_desc',
            'newest', 'recent' => 'newest',
            '' => 'sold_desc',
            default => 'sold_desc',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function indexSortLabels(): array
    {
        return [
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'sold_desc' => 'Most sold',
            'sold_asc' => 'Least sold',
            'price_asc' => 'Cheapest (selling ₱)',
            'price_desc' => 'Most expensive (selling ₱)',
        ];
    }

    /**
     * Paginated list for cashier/admin inventory screens (bounded page size, selective columns).
     * Adds sold_qty from paid invoice line items. Sort via {@see normalizeIndexSort()}.
     */
    public static function paginateForIndex(Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $q = trim((string) $request->get('q', ''));
        $sort = static::normalizeIndexSort($request->get('sort'));

        $soldSub = static::invoiceSoldQuantitySubquery();

        $query = static::query()
            ->from('inventories')
            ->leftJoinSub($soldSub, 'inv_sold', function ($join): void {
                $join->on('inv_sold.part_id', '=', 'inventories.id');
            })
            ->select([
                'inventories.id',
                'inventories.item_name',
                'inventories.quantity',
                'inventories.part_number',
                'inventories.acquisition_price',
                'inventories.supplier',
                'inventories.selling',
                'inventories.created_at',
            ])
            ->addSelect(DB::raw('CAST(COALESCE(inv_sold.sold_qty, 0) AS UNSIGNED) as sold_qty'));

        static::applySearchFilter($query, $q);

        switch ($sort) {
            case 'oldest':
                $query->orderBy('inventories.created_at')->orderBy('inventories.id');
                break;
            case 'sold_desc':
                $query->orderByDesc('sold_qty')
                    ->orderBy('inventories.item_name')
                    ->orderBy('inventories.id');
                break;
            case 'sold_asc':
                $query->orderBy('sold_qty')
                    ->orderBy('inventories.item_name')
                    ->orderBy('inventories.id');
                break;
            case 'price_asc':
                $query->orderBy('inventories.selling')
                    ->orderBy('inventories.item_name')
                    ->orderBy('inventories.id');
                break;
            case 'price_desc':
                $query->orderByDesc('inventories.selling')
                    ->orderBy('inventories.item_name')
                    ->orderBy('inventories.id');
                break;
            default:
                $query->orderByDesc('inventories.created_at')->orderBy('inventories.id');
                break;
        }

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
                    $pattern = '%'.addcslashes($needle, '%_\\').'%';

                    $inner->where(function (Builder $w) use ($pattern, $t, $rawTok) {
                        $w->whereRaw('LOWER(TRIM(`'.$t.'`.`item_name`)) LIKE ?', [$pattern])
                            ->orWhereRaw(
                                'LOWER(TRIM(CAST(`'.$t.'`.`part_number` AS CHAR))) LIKE ?',
                                [$pattern]
                            )
                            ->orWhereRaw(
                                'LOWER(TRIM(COALESCE(`'.$t.'`.`supplier`, \'\'))) LIKE ?',
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
                                $w->orWhere($t.'.id', $id);
                            }
                            if ($rawTok === '0') {
                                $w->orWhere($t.'.id', 0);
                            }
                        }
                    });
                });
            }
        });
    }
}
