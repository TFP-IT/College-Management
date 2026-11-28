<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The gateway can flag a payment as risky, and the code stores it as "hold". The column did not
 * allow that value. Adding a value to the end of an enum keeps every existing row as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'hold'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Left in place: removing the value would break rows that are on hold.
    }
};
