<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\OilChangeReminderService;
use Illuminate\Console\Command;

class SyncPaidInvoiceOilChangeReminders extends Command
{
    protected $signature = 'sms:sync-paid-invoices
                            {--invoicing : Only invoices where source_type is invoicing}';

    protected $description = 'Rebuild oil-change SMS reminders from paid invoices (e.g. after DB import)';

    public function handle(OilChangeReminderService $reminderService): int
    {
        $pruned = $reminderService->prunePastDuePendingReminders();
        if ($pruned > 0) {
            $this->line("Removed {$pruned} reminder(s) whose due date is before today (before sync).");
        }

        $query = Invoice::query()
            ->where('status', 'paid')
            ->with(['jobs', 'client', 'vehicle'])
            ->orderBy('id');

        if ($this->option('invoicing')) {
            $query->where('source_type', 'invoicing');
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No paid invoices matched.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} paid invoice(s)".($this->option('invoicing') ? ' (source_type=invoicing only).' : '.'));

        $scheduled = 0;
        $skippedNoOilJob = 0;
        $skippedNoPhone = 0;
        $skippedPastDue = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function ($invoices) use ($reminderService, &$scheduled, &$skippedNoOilJob, &$skippedNoPhone, &$skippedPastDue, $bar) {
            foreach ($invoices as $invoice) {
                $result = $reminderService->syncForInvoice($invoice);
                if ($result['scheduled']) {
                    $scheduled++;
                } elseif ($result['warning'] !== null) {
                    $skippedNoPhone++;
                } elseif (! empty($result['past_due'])) {
                    $skippedPastDue++;
                } else {
                    $skippedNoOilJob++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Reminders created / updated (pending)', $scheduled],
                ['Skipped (no oil-change job match)', $skippedNoOilJob],
                ['Skipped (no valid phone)', $skippedNoPhone],
                ['Skipped / cleared (due date already passed)', $skippedPastDue],
            ]
        );

        $this->comment('Open SMS in the app to see pending reminders. Already-sent rows are not recreated.');

        return self::SUCCESS;
    }
}
