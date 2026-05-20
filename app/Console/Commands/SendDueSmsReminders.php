<?php

namespace App\Console\Commands;

use App\Models\SmsLog;
use App\Models\SmsReminder;
use App\Models\SmsSetting;
use App\Services\OilChangeReminderService;
use App\Services\PhilSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDueSmsReminders extends Command
{
    protected $signature = 'sms:send-due-reminders';

    protected $description = 'Send pending oil-change SMS reminders due today via PhilSMS';

    public function handle(PhilSmsService $philSms, OilChangeReminderService $reminderService): int
    {
        $pruned = $reminderService->prunePastDuePendingReminders();
        if ($pruned > 0) {
            $this->line("Removed {$pruned} reminder(s) whose due date is before today.");
        }

        $settings = SmsSetting::current();
        if (! $settings->enabled) {
            $this->info('SMS reminders are disabled in settings.');

            return self::SUCCESS;
        }

        $today = Carbon::today()->toDateString();

        $reminders = SmsReminder::query()
            ->where('status', SmsReminder::STATUS_PENDING)
            ->whereDate('remind_on', '<=', $today)
            ->get();

        if ($reminders->isEmpty()) {
            $this->info('No due reminders.');

            return self::SUCCESS;
        }

        $senderId = $settings->sender_id ?: config('philsms.sender_id');
        $sent = 0;
        $failed = 0;

        foreach ($reminders as $reminder) {
            $message = $reminderService->renderTemplate($settings, $reminder);
            $result = $philSms->send($reminder->phone, $message, $senderId);

            if ($result['success']) {
                SmsLog::query()->create([
                    'phone' => $reminder->phone,
                    'message' => $message,
                    'status' => 'sent',
                    'provider_response' => is_string($result['data'] ?? null)
                        ? $result['data']
                        : json_encode($result['data'] ?? []),
                    'sms_reminder_id' => $reminder->id,
                ]);
                $reminderId = $reminder->id;
                $phone = $reminder->phone;
                $reminder->delete();
                $sent++;
                $this->line("Sent reminder #{$reminderId} to {$phone} (removed from reminders table)");
            } else {
                $reminder->update([
                    'status' => SmsReminder::STATUS_FAILED,
                    'error_message' => $result['message'] ?? 'Unknown error',
                ]);
                SmsLog::query()->create([
                    'phone' => $reminder->phone,
                    'message' => $message,
                    'status' => 'failed',
                    'error_message' => $result['message'] ?? 'Unknown error',
                    'provider_response' => isset($result['data']) ? json_encode($result['data']) : null,
                    'sms_reminder_id' => $reminder->id,
                ]);
                $failed++;
                $this->error("Failed reminder #{$reminder->id}: ".($result['message'] ?? 'Unknown'));
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
