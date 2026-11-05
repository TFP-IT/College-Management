<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_profiles')) {
            Schema::create('student_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('user_uuid')->nullable()->index();
                $table->string('student_number', 50)->unique();
                $table->string('blood_group', 10)->nullable();
                $table->date('dob')->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('nationality', 50)->default('Bangladeshi');
                $table->text('current_address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('guardian_profiles')) {
            Schema::create('guardian_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('user_uuid')->nullable()->index();
                $table->string('relation', 50);
                $table->string('occupation', 100)->nullable();
                $table->text('nid_passport')->nullable(); // encrypted cast
                $table->string('emergency_contact', 20)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_guardians')) {
            Schema::create('student_guardians', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('student_profiles')->cascadeOnDelete();
                $table->foreignUuid('guardian_id')->constrained('guardian_profiles')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->unique(['student_id', 'guardian_id']);
            });
        }

        if (!Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('student_id')->constrained('student_profiles')->cascadeOnDelete();
                $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignUuid('class_id')->constrained('academic_classes')->cascadeOnDelete();
                $table->foreignUuid('group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
                $table->foreignUuid('section_id')->constrained('academic_sections')->cascadeOnDelete();
                $table->string('roll_no', 20)->nullable();
                $table->enum('status', ['ACTIVE', 'TRANSFERRED', 'COMPLETED'])->default('ACTIVE');
                $table->date('enrolled_on');
                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('student_guardians');
        Schema::dropIfExists('guardian_profiles');
        Schema::dropIfExists('student_profiles');
    }
};
