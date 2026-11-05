<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('teacher_permissions')) {
            Schema::create('teacher_permissions', function (Blueprint $table) {
                $table->id();
                $table->integer('class_id')->nullable();
                $table->integer('section_id')->nullable();
                $table->integer('school_id')->nullable();
                $table->integer('teacher_id')->nullable();
                $table->integer('marks')->nullable();
                $table->integer('attendance')->nullable();
                $table->integer('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teacher_permissions');
    }
};
