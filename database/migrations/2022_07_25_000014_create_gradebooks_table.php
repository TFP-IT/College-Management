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
        if (!Schema::hasTable('gradebooks')) {
            Schema::create('gradebooks', function (Blueprint $table) {
                $table->id();
                $table->integer('class_id');
                $table->integer('section_id');
                $table->integer('student_id');
                $table->integer('exam_category_id');
                $table->string('marks')->nullable();
                $table->string('comment');
                $table->integer('school_id');
                $table->integer('session_id');
                $table->integer('timestamp');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gradebooks');
    }
};
