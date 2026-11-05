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
        if (!Schema::hasTable('admit_cards')) {
            Schema::create('admit_cards', function (Blueprint $table) {
                $table->id();
                $table->string('template');
                $table->string('heading')->nullable();
                $table->string('title')->nullable();
                $table->integer('school_id')->nullable();
                $table->string('exam_center')->nullable();
                $table->string('footer_text')->nullable();
                $table->string('left_logo')->nullable();
                $table->string('right_logo')->nullable();
                $table->string('sign')->nullable();
                $table->string('background_image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admit_cards');
    }
};
