<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_collections', function (Blueprint $table) {
            $table->index('date', 'ar_collections_date_index');
        });
        Schema::table('cash_deposits', function (Blueprint $table) {
            $table->index('date', 'cash_deposits_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('ar_collections', function (Blueprint $table) {
            $table->dropIndex('ar_collections_date_index');
        });
        Schema::table('cash_deposits', function (Blueprint $table) {
            $table->dropIndex('cash_deposits_date_index');
        });
    }
};
