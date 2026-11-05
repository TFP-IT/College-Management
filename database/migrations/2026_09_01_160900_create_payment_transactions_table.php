<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->morphs('payable');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                
                $table->enum('type', ['admission_fee', 'monthly_tuition', 'exam_fee', 'hostel_fee', 'other']);
                $table->string('period', 7)->nullable();
                
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('BDT');
                $table->enum('method', ['bkash', 'nagad', 'rocket', 'card', 'bank', 'cash'])->nullable();
                $table->string('transaction_id')->nullable()->unique();
                
                $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->json('gateway_response')->nullable();
                
                $table->timestamps();
                
                $table->index(['status', 'type']);
                // Unique composite index for idempotency
                $table->unique(['payable_type', 'payable_id', 'type', 'period'], 'payment_transactions_payable_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
