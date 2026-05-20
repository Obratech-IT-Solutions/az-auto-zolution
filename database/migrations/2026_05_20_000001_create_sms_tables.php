<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('reminder_days_before')->default(5);
            $table->unsignedSmallInteger('interval_months')->default(6);
            $table->string('oil_change_match')->default('change oil');
            $table->text('message_template');
            $table->string('sender_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('sms_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('phone', 32);
            $table->string('customer_name')->nullable();
            $table->string('plate')->nullable();
            $table->date('oil_change_date');
            $table->date('due_date');
            $table->date('remind_on');
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('provider_message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'remind_on']);
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32);
            $table->text('message');
            $table->string('status', 20);
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sms_reminder_id')->nullable()->constrained('sms_reminders')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('sms_reminders');
        Schema::dropIfExists('sms_settings');
    }
};
