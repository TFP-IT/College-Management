<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 50); // e.g. 2026-27
                $table->date('starts_on');
                $table->date('ends_on');
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('academic_classes')) {
            Schema::create('academic_classes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 20); // PG, NURSERY, KG, 1..10, XI, XII
                $table->string('name', 100);
                $table->enum('section_type', ['SCHOOL', 'COLLEGE']);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('academic_groups')) {
            Schema::create('academic_groups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('class_id')->constrained('academic_classes')->cascadeOnDelete();
                $table->string('code', 50); // SCIENCE, BUSINESS, HUMANITIES
                $table->string('name', 100);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('academic_sections')) {
            Schema::create('academic_sections', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignUuid('class_id')->constrained('academic_classes')->cascadeOnDelete();
                $table->foreignUuid('group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
                $table->string('name', 50); // A, B
                $table->integer('capacity')->default(40);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sections');
        Schema::dropIfExists('academic_groups');
        Schema::dropIfExists('academic_classes');
        Schema::dropIfExists('academic_years');
    }
};
