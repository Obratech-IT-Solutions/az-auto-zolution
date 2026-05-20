<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceJob;
use App\Models\SmsReminder;
use App\Models\SmsSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OilChangeReminderService
{
    public function __construct(
        protected PhilSmsService $philSms
    ) {}

    /**
     * @return array{scheduled: bool, warning: ?string, past_due: bool}
     */
    public function syncForInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['jobs', 'client', 'vehicle']);

        if ($invoice->status !== 'paid') {
            $this->cancelPendingForInvoice($invoice->id);

            return ['scheduled' => false, 'warning' => null, 'past_due' => false];
        }

        if (! $this->hasOilChangeJob($invoice)) {
            $this->cancelPendingForInvoice($invoice->id);

            return ['scheduled' => false, 'warning' => null, 'past_due' => false];
        }

        $phone = $invoice->resolvedCustomerPhone();
        $normalized = $phone ? $this->philSms->normalizePhone($phone) : null;
        if (! $normalized) {
            $this->cancelPendingForInvoice($invoice->id);

            return [
                'scheduled' => false,
                'warning' => 'Oil change reminder not scheduled: customer has no valid phone number.',
                'past_due' => false,
            ];
        }

        $settings = SmsSetting::current();
        $oilChangeDate = $this->invoiceOilChangeServiceDate($invoice);
        $dueDate = $oilChangeDate->copy()->addMonths($settings->interval_months);
        $remindOn = $dueDate->copy()->subDays($settings->reminder_days_before);

        if ($dueDate->lt(Carbon::today())) {
            $this->deletePendingRemindersForInvoice($invoice->id);

            return ['scheduled' => false, 'warning' => null, 'past_due' => true];
        }

        $customerName = trim((string) ($invoice->customer_name ?? $invoice->client?->name ?? 'Customer'));
        if ($customerName === '' || \App\Models\Client::isPlaceholderLabel($customerName)) {
            $customerName = 'Customer';
        }

        $plate = trim((string) ($invoice->vehicle?->plate_number ?? $invoice->vehicle_name ?? ''));

        SmsReminder::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', SmsReminder::STATUS_PENDING)
            ->delete();

        SmsReminder::query()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'vehicle_id' => $invoice->vehicle_id,
            'phone' => $normalized,
            'customer_name' => $customerName,
            'plate' => $plate !== '' ? $plate : null,
            'oil_change_date' => $oilChangeDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'remind_on' => $remindOn->toDateString(),
            'status' => SmsReminder::STATUS_PENDING,
        ]);

        return ['scheduled' => true, 'warning' => null, 'past_due' => false];
    }

    /**
     * Delete pending or failed reminders whose oil-change due date is already before today
     * (they are obsolete in the list and are not sent by the daily job).
     */
    public function prunePastDuePendingReminders(): int
    {
        return SmsReminder::query()
            ->whereIn('status', [SmsReminder::STATUS_PENDING, SmsReminder::STATUS_FAILED])
            ->whereDate('due_date', '<', Carbon::today())
            ->delete();
    }

    /**
     * Best-effort service date for the oil change: earliest of invoice date, appointment date,
     * and matching labor rows' created_at. Capped to today so bad future timestamps do not skew due dates.
     */
    protected function invoiceOilChangeServiceDate(Invoice $invoice): Carbon
    {
        $today = Carbon::today();
        $dates = [Carbon::parse($invoice->created_at)->startOfDay()];

        if ($invoice->appointment_date) {
            $dates[] = Carbon::parse($invoice->appointment_date)->startOfDay();
        }

        foreach ($invoice->jobs as $job) {
            if ($this->jobMatchesOilChange($job)) {
                $dates[] = Carbon::parse($job->created_at)->startOfDay();
            }
        }

        /** @var Carbon $anchor */
        $anchor = collect($dates)->sortBy(fn (Carbon $d) => $d->timestamp)->first();

        return $anchor->copy()->min($today);
    }

    protected function deletePendingRemindersForInvoice(int $invoiceId): void
    {
        SmsReminder::query()
            ->where('invoice_id', $invoiceId)
            ->where('status', SmsReminder::STATUS_PENDING)
            ->delete();
    }

    public function renderTemplate(SmsSetting $settings, SmsReminder $reminder): string
    {
        $dueFormatted = Carbon::parse($reminder->due_date)->format('M j, Y');
        $replacements = [
            '{customer_name}' => $reminder->customer_name ?? 'Customer',
            '{plate}' => $reminder->plate ?? 'your vehicle',
            '{due_date}' => $dueFormatted,
            '{oil_change_date}' => Carbon::parse($reminder->oil_change_date)->format('M j, Y'),
            '{remind_on}' => Carbon::parse($reminder->remind_on)->format('M j, Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $settings->message_template);
    }

    protected function hasOilChangeJob(Invoice $invoice): bool
    {
        foreach ($invoice->jobs as $job) {
            if ($this->jobMatchesOilChange($job)) {
                return true;
            }
        }

        return false;
    }

    protected function jobMatchesOilChange(InvoiceJob $job): bool
    {
        $settings = SmsSetting::current();
        $phrases = $this->oilChangeMatchPhrases($settings->oil_change_match);
        $normalized = $this->normalizeJobDescriptionForMatch((string) $job->job_description);
        if ($normalized === '') {
            return false;
        }
        foreach ($phrases as $phrase) {
            if ($phrase !== '' && Str::contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function oilChangeMatchPhrases(?string $oilChangeMatch): array
    {
        $raw = Str::lower(trim((string) $oilChangeMatch));
        if ($raw === '') {
            $raw = 'change oil';
        }

        $fromSettings = collect(preg_split('/\s*,\s*/', $raw) ?: [])
            ->map(fn (string $p) => $this->normalizeJobDescriptionForMatch($p))
            ->filter()
            ->values()
            ->all();

        $synonyms = ['change oil', 'oil change'];
        $merged = array_values(array_unique(array_merge($fromSettings, $synonyms)));

        return array_values(array_filter($merged, fn (string $p) => $p !== ''));
    }

    /**
     * Lowercase, turn commas/slashes/dashes into spaces, collapse whitespace
     * so "LABOR, CHANGE OIL" matches "change oil".
     */
    protected function normalizeJobDescriptionForMatch(string $text): string
    {
        $lower = Str::lower(trim($text));
        $repl = [',', '/', '|', '-', '_', "\r", "\n", "\t", '　', '，', '／'];
        $lower = str_replace($repl, ' ', $lower);
        $lower = preg_replace('/\s+/u', ' ', $lower) ?? '';

        return trim($lower);
    }

    protected function cancelPendingForInvoice(int $invoiceId): void
    {
        SmsReminder::query()
            ->where('invoice_id', $invoiceId)
            ->where('status', SmsReminder::STATUS_PENDING)
            ->update(['status' => SmsReminder::STATUS_CANCELLED]);
    }
}
