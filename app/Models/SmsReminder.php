<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsReminder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_id',
        'client_id',
        'vehicle_id',
        'phone',
        'customer_name',
        'plate',
        'oil_change_date',
        'due_date',
        'remind_on',
        'status',
        'sent_at',
        'provider_message',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'oil_change_date' => 'date',
            'due_date' => 'date',
            'remind_on' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }
}
