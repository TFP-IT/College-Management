<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_programs')) {
            Schema::create('admission_programs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 50)->unique();
                $table->string('title', 255);
                $table->enum('section_type', ['SCHOOL', 'COLLEGE']);
                $table->foreignUuid('class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
                $table->foreignUuid('group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->json('min_eligibility')->nullable();
                $table->decimal('fee_application', 12, 2)->default(0.00);
                $table->decimal('fee_admission', 12, 2)->default(0.00);
                $table->integer('seats')->default(0);
                $table->dateTime('application_opens_at')->nullable();
                $table->dateTime('application_deadline')->nullable();
                $table->boolean('requires_assessment')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'section_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_programs');
    }
};
