<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'created_by_user_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->after('address')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('invoices', 'last_processed_by_user_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('last_processed_by_user_id')
                    ->nullable()
                    ->after('created_by_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'last_processed_by_user_id')) {
                $table->dropForeign(['last_processed_by_user_id']);
                $table->dropColumn('last_processed_by_user_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'created_by_user_id')) {
                $table->dropForeign(['created_by_user_id']);
                $table->dropColumn('created_by_user_id');
            }
        });
    }
};
