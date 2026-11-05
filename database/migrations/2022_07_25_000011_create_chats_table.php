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
        if (!Schema::hasTable('chats')) {
            Schema::create('chats', function (Blueprint $table) {
                $table->id();
                $table->integer('message_thrade')->nullable();
                $table->integer('reciver_id')->nullable();
                $table->integer('sender_id')->nullable();
                $table->longText('message')->nullable();
                $table->integer('reply_id')->nullable();
                $table->integer('school_id')->nullable();
                $table->integer('read_status')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
