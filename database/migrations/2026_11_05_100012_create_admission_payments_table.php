<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_payments')) {
            Schema::create('admission_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('payment_type', 50); // APPLICATION_FEE, ADMISSION_FEE
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('BDT');
                $table->string('provider', 50); // mock, sslcommerz, bkash, nagad
                $table->string('transaction_ref', 100)->unique();
                $table->string('payment_status', 50)->default('INITIATED')->index(); // INITIATED, PENDING, SUCCESS, FAILED, CANCELLED, REFUNDED
                $table->json('payment_details')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'payment_type', 'payment_status'], 'adm_pay_app_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_payments');
    }
};
