<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('application_no', 50)->unique();
                $table->foreignUuid('program_id')->constrained('admission_programs')->cascadeOnDelete();
                $table->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('applicant_name', 255);
                $table->string('email', 255)->nullable()->index();
                $table->string('mobile', 20)->index();
                $table->date('dob');
                $table->string('gender', 20);
                $table->json('academic_history')->nullable();
                $table->string('status', 50)->default('DRAFT')->index();
                $table->timestamp('submitted_at')->nullable();
                $table->string('qr_token_hash', 64)->nullable()->index();
                $table->timestamp('qr_expires_at')->nullable();
                $table->timestamp('qr_used_at')->nullable();
                $table->foreignUuid('enrolled_student_id')->nullable()->constrained('student_profiles')->nullOnDelete();
                $table->text('council_notes')->nullable();
                $table->timestamps();

                $table->unique(['program_id', 'mobile', 'dob'], 'applications_program_mobile_dob_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
