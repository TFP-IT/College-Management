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
        if (!Schema::hasTable('message_thrades')) {
            Schema::create('message_thrades', function (Blueprint $table) {
                $table->id();
                $table->integer('reciver_id')->nullable();
                $table->integer('sender_id')->nullable();
                $table->integer('school_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_thrades');
    }
};
