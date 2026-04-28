<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'cashless_tender_amount')) {
                $table->decimal('cashless_tender_amount', 15, 2)->nullable()->after('cash_change_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'cashless_tender_amount')) {
                $table->dropColumn('cashless_tender_amount');
            }
        });
    }
};
