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
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->longText('expense_type');
                $table->integer('expense_id');
                $table->integer('user_id');
                $table->string('payment_method');
                $table->decimal('amount', 10, 2);
                $table->string('status');
                $table->longText('transaction_keys');
                $table->string('created_at')->nullable();
                $table->string('updated_at')->nullable();
                $table->integer('school_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
