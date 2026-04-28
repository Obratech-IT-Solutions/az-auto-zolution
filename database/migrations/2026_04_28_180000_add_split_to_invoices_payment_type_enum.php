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
        $row = DB::selectOne('SHOW COLUMNS FROM `invoices` WHERE Field = ?', ['payment_type']);
        if (! $row) {
            return;
        }
        if (str_contains((string) $row->Type, 'split')) {
            return;
        }

        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `payment_type` ENUM('cash','debit','credit','non_cash','gcash','split') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        $row = DB::selectOne('SHOW COLUMNS FROM `invoices` WHERE Field = ?', ['payment_type']);
        if (! $row || ! str_contains((string) $row->Type, 'split')) {
            return;
        }

        DB::table('invoices')->where('payment_type', 'split')->update(['payment_type' => null]);

        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `payment_type` ENUM('cash','debit','credit','non_cash','gcash') NULL DEFAULT NULL");
    }
};
