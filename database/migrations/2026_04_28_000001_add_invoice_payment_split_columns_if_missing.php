<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'payment_cash_amount')) {
                $table->decimal('payment_cash_amount', 15, 2)->nullable()->after('grand_total');
            }
            if (! Schema::hasColumn('invoices', 'payment_non_cash_amount')) {
                $table->decimal('payment_non_cash_amount', 15, 2)->nullable()->after('payment_cash_amount');
            }
            if (! Schema::hasColumn('invoices', 'cash_tender_amount')) {
                $table->decimal('cash_tender_amount', 15, 2)->nullable()->after('payment_non_cash_amount');
            }
            if (! Schema::hasColumn('invoices', 'cash_change_amount')) {
                $table->decimal('cash_change_amount', 15, 2)->nullable()->after('cash_tender_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['cash_change_amount', 'cash_tender_amount', 'payment_non_cash_amount', 'payment_cash_amount'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
