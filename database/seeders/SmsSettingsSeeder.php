<?php

namespace Database\Seeders;

use App\Models\SmsSetting;
use Illuminate\Database\Seeder;

class SmsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (SmsSetting::query()->exists()) {
            return;
        }

        SmsSetting::query()->create([
            'reminder_days_before' => 5,
            'interval_months' => 6,
            'oil_change_match' => 'change oil',
            'message_template' => 'Hi {customer_name}, your oil change for {plate} is due on {due_date}. Visit AZ Auto Zolutions to schedule. Thank you!',
            'enabled' => true,
        ]);
    }
}
