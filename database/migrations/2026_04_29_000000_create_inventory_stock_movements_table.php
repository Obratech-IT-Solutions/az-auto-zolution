<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_stock_movements')) {
            return;
        }

        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction', 16);
            $table->unsignedInteger('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reason', 1000)->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['inventory_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
    }
};
