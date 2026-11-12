<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routine_class_statuses')) {
            return;
        }

        Schema::create('routine_class_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('routine_id');
            $table->unsignedBigInteger('teacher_id');
            $table->date('class_date');
            $table->string('status', 20);
            $table->timestamps();
            $table->unique(['routine_id', 'class_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_class_statuses');
    }
};
