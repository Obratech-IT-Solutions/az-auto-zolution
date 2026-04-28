<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillClientNames extends Command
{
    protected $signature = 'clients:backfill-names {--dry-run : Show counts only, no updates}';

    protected $description = 'Set empty clients.name from latest invoice customer_name, else first vehicle plate (with id for uniqueness)';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $driver = DB::getDriverName();
        $shortSql = $driver === 'sqlite'
            ? 'LENGTH(TRIM(COALESCE(name, \'\'))) <= 1'
            : 'CHAR_LENGTH(TRIM(COALESCE(name, \'\'))) <= 1';

        $query = Client::query()->where(function ($w) use ($shortSql) {
            $w->whereNull('name')
                ->orWhereRaw('TRIM(name) = ?', [''])
                ->orWhereRaw('LOWER(TRIM(name)) IN (?,?,?,?,?,?,?)', ['0', '-', '—', 'n/a', 'na', 'none', '.'])
                ->orWhereRaw($shortSql);
        });

        $total = (clone $query)->count();
        $this->info("Clients with empty name: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($dry) {
            $fromInv = 0;
            $fromPlate = 0;
            $still = 0;

            $query->orderBy('id')->each(function (Client $c) use (&$fromInv, &$fromPlate, &$still) {
                if ($this->latestInvoiceCustomerName($c->id)) {
                    $fromInv++;

                    return;
                }
                if ($this->firstPlate($c->id)) {
                    $fromPlate++;

                    return;
                }
                $still++;
            }, 500);

            $this->table(
                ['Would set from', 'Count'],
                [
                    ['Latest invoice customer_name', $fromInv],
                    ['Vehicle plate + id', $fromPlate],
                    ['No invoice/plate — unchanged', $still],
                ]
            );

            return self::SUCCESS;
        }

        $updatedInvoice = 0;
        $updatedPlate = 0;

        $query->orderBy('id')->chunkById(200, function ($clients) use (&$updatedInvoice, &$updatedPlate) {
            foreach ($clients as $c) {
                $newName = null;

                $cn = $this->latestInvoiceCustomerName($c->id);
                if ($cn !== null) {
                    $newName = $cn;
                    $updatedInvoice++;
                } else {
                    $plate = $this->firstPlate($c->id);
                    if ($plate !== null) {
                        $newName = sprintf('%s (#%d)', $plate, $c->id);
                        $updatedPlate++;
                    }
                }

                if ($newName !== null) {
                    Client::whereKey($c->id)->update(['name' => $newName]);
                }
            }
        });

        $this->info("Updated from invoice customer_name: {$updatedInvoice}");
        $this->info("Updated from vehicle plate: {$updatedPlate}");

        return self::SUCCESS;
    }

    private function latestInvoiceCustomerName(int $clientId): ?string
    {
        $cn = Invoice::query()
            ->where('client_id', $clientId)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->orderByDesc('created_at')
            ->value('customer_name');
        $cn = trim((string) $cn);

        return $cn !== '' ? $cn : null;
    }

    private function firstPlate(int $clientId): ?string
    {
        $plate = Vehicle::query()
            ->where('client_id', $clientId)
            ->whereNotNull('plate_number')
            ->where('plate_number', '!=', '')
            ->orderBy('id')
            ->value('plate_number');
        $plate = trim((string) $plate);

        return $plate !== '' ? $plate : null;
    }
}
