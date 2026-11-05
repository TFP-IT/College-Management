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
        if (!Schema::hasTable('payment_history')) {
            Schema::create('payment_history', function (Blueprint $table) {
                $table->id();
                $table->string('payment_type')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('course_id')->nullable();
                $table->integer('package_id')->nullable();
                $table->float('amount')->nullable();
                $table->integer('school_id')->nullable();
                $table->longText('transaction_keys')->nullable();
                $table->string('document_image')->nullable();
                $table->string('paid_by')->nullable();
                $table->string('status')->nullable();
                $table->integer('timestamp')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};
