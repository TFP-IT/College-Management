<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('number_sequences')) {
            Schema::create('number_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('key_name', 50);
                $table->integer('year');
                $table->integer('last_value')->default(0);
                $table->timestamps();

                $table->unique(['key_name', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
