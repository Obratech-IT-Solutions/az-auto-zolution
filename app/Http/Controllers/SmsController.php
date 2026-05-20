<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SmsLog;
use App\Models\SmsReminder;
use App\Models\SmsSetting;
use App\Services\OilChangeReminderService;
use App\Services\PhilSmsService;
use App\Support\CashierListLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function index(Request $request): View
    {
        $settings = SmsSetting::current();
        app(OilChangeReminderService::class)->prunePastDuePendingReminders();

        $reminders = SmsReminder::query()
            ->with(['invoice', 'client', 'vehicle'])
            ->orderByDesc('remind_on')
            ->paginate(CashierListLimits::SMS_REMINDERS_PER_PAGE)
            ->withQueryString();
        $logs = SmsLog::query()
            ->with('sentBy')
            ->latest()
            ->limit(50)
            ->get();

        $layout = $request->routeIs('admin.*') ? 'layouts.admin' : 'layouts.cashier';

        $philSms = app(PhilSmsService::class);
        $uniqueManualSmsRecipientCount = Client::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone')
            ->map(fn ($p) => $philSms->normalizePhone((string) $p))
            ->filter()
            ->unique()
            ->count();

        return view('sms.index', compact(
            'settings',
            'reminders',
            'logs',
            'layout',
            'uniqueManualSmsRecipientCount',
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reminder_days_before' => 'required|integer|min:0|max:365',
            'interval_months' => 'required|integer|min:1|max:36',
            'oil_change_match' => 'required|string|max:100',
            'message_template' => 'required|string|max:500',
            'sender_id' => 'nullable|string|max:11',
            'enabled' => 'nullable|boolean',
        ]);

        $settings = SmsSetting::current();
        $settings->update([
            'reminder_days_before' => $validated['reminder_days_before'],
            'interval_months' => $validated['interval_months'],
            'oil_change_match' => $validated['oil_change_match'],
            'message_template' => $validated['message_template'],
            'sender_id' => $validated['sender_id'] ?? null,
            'enabled' => $request->boolean('enabled'),
        ]);

        return $this->backToSms('SMS settings saved.');
    }

    public function sendManual(Request $request, PhilSmsService $philSms): RedirectResponse
    {
        $validated = $request->validate([
            'send_to_all_clients' => 'nullable|boolean',
            'phone' => 'required_unless:send_to_all_clients,1|nullable|string|max:32',
            'message' => 'required|string|max:500',
        ]);

        $settings = SmsSetting::current();
        $senderId = $settings->sender_id ?: config('philsms.sender_id');

        if ($request->boolean('send_to_all_clients')) {
            return $this->sendManualToAllClients($philSms, $validated['message'], $senderId);
        }

        $normalized = $philSms->normalizePhone($validated['phone'] ?? '');

        if (! $normalized) {
            return $this->backToSms('Invalid phone number.', 'error');
        }

        $result = $philSms->send($normalized, $validated['message'], $senderId);

        SmsLog::query()->create([
            'phone' => $normalized,
            'message' => $validated['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_response' => isset($result['data']) ? json_encode($result['data']) : null,
            'error_message' => $result['success'] ? null : ($result['message'] ?? 'Send failed'),
            'sent_by_user_id' => Auth::id(),
        ]);

        if (! $result['success']) {
            return $this->backToSms($result['message'] ?? 'SMS send failed.', 'error');
        }

        return $this->backToSms('SMS sent successfully.');
    }

    protected function sendManualToAllClients(PhilSmsService $philSms, string $message, ?string $senderId): RedirectResponse
    {
        $numbers = Client::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->pluck('phone')
            ->map(fn ($p) => $philSms->normalizePhone((string) $p))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return $this->backToSms('No clients have a valid phone number on file.', 'error');
        }

        $success = 0;
        $failed = 0;
        $lastError = null;

        foreach ($numbers as $normalized) {
            $result = $philSms->send($normalized, $message, $senderId);

            SmsLog::query()->create([
                'phone' => $normalized,
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_response' => isset($result['data']) ? json_encode($result['data']) : null,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Send failed'),
                'sent_by_user_id' => Auth::id(),
            ]);

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
                $lastError = $result['message'] ?? 'Send failed';
            }
        }

        $summary = "Broadcast finished: {$success} sent, {$failed} failed ({$numbers->count()} unique numbers).";

        if ($success === 0) {
            return $this->backToSms(trim($summary.' '.(string) $lastError), 'error');
        }

        if ($failed > 0) {
            return $this->backToSms($summary, 'warning');
        }

        return $this->backToSms($summary);
    }

    public function sendReminderNow(int $id, PhilSmsService $philSms, OilChangeReminderService $reminderService): RedirectResponse
    {
        $reminder = SmsReminder::query()->findOrFail($id);

        if ($reminder->status === SmsReminder::STATUS_SENT) {
            return $this->backToSms('This reminder was already sent.', 'error');
        }

        $settings = SmsSetting::current();
        $message = $reminderService->renderTemplate($settings, $reminder);
        $senderId = $settings->sender_id ?: config('philsms.sender_id');
        $result = $philSms->send($reminder->phone, $message, $senderId);

        if ($result['success']) {
            $reminder->update([
                'status' => SmsReminder::STATUS_SENT,
                'sent_at' => now(),
                'provider_message' => is_string($result['data'] ?? null)
                    ? $result['data']
                    : json_encode($result['data'] ?? []),
                'error_message' => null,
            ]);
        } else {
            $reminder->update([
                'status' => SmsReminder::STATUS_FAILED,
                'error_message' => $result['message'] ?? 'Unknown error',
            ]);
        }

        SmsLog::query()->create([
            'phone' => $reminder->phone,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_response' => isset($result['data']) ? json_encode($result['data']) : null,
            'error_message' => $result['success'] ? null : ($result['message'] ?? 'Send failed'),
            'sent_by_user_id' => Auth::id(),
            'sms_reminder_id' => $reminder->id,
        ]);

        if (! $result['success']) {
            return $this->backToSms($result['message'] ?? 'SMS send failed.', 'error');
        }

        return $this->backToSms('Reminder SMS sent.');
    }

    protected function backToSms(string $message, string $type = 'success'): RedirectResponse
    {
        $route = request()->routeIs('admin.*') ? 'admin.sms.index' : 'cashier.sms.index';

        return redirect()->route($route)->with($type, $message);
    }
}
