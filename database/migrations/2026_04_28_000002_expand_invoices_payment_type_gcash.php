<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $row = DB::selectOne("SHOW COLUMNS FROM `invoices` WHERE Field = ?", ['payment_type']);
        if (! $row || str_contains((string) $row->Type, 'gcash')) {
            return;
        }

        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `payment_type` ENUM('cash','debit','credit','non_cash','gcash') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $row = DB::selectOne("SHOW COLUMNS FROM `invoices` WHERE Field = ?", ['payment_type']);
        if (! $row || ! str_contains((string) $row->Type, 'gcash')) {
            return;
        }

        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `payment_type` ENUM('cash','debit','credit','non_cash') NULL DEFAULT NULL");
    }
};
