<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockMovement extends Model
{
    public const DIRECTION_ADD = 'add';

    public const DIRECTION_REMOVE = 'remove';

    public $timestamps = false;

    protected $fillable = [
        'inventory_id',
        'user_id',
        'direction',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reason',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
