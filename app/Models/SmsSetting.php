<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = [
        'reminder_days_before',
        'interval_months',
        'oil_change_match',
        'message_template',
        'sender_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'reminder_days_before' => 'integer',
            'interval_months' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->first();

        if ($settings) {
            return $settings;
        }

        return static::query()->create([
            'reminder_days_before' => 5,
            'interval_months' => 6,
            'oil_change_match' => 'change oil',
            'message_template' => 'Hi {customer_name}, your oil change for {plate} is due on {due_date}. Visit AZ Auto Zolutions to schedule. Thank you!',
            'enabled' => true,
        ]);
    }
}
