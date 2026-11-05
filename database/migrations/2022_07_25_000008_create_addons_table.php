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
        if (!Schema::hasTable('addons')) {
            Schema::create('addons', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->integer('parent_id')->nullable();
                $table->string('features')->nullable();
                $table->float('version')->nullable();
                $table->string('purchase_code')->nullable();
                $table->string('unique_identifier')->nullable();
                $table->integer('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
