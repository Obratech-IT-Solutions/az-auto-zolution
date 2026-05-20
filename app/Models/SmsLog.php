<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'phone',
        'message',
        'status',
        'provider_response',
        'error_message',
        'sent_by_user_id',
        'sms_reminder_id',
    ];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(SmsReminder::class, 'sms_reminder_id');
    }
}
