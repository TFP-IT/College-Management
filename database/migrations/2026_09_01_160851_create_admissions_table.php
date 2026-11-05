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
        if (!Schema::hasTable('admissions')) {
            Schema::create('admissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
                
                $table->string('application_no')->unique();
                $table->enum('department', ['Science', 'Commerce', 'Arts']);
                $table->string('ssc_board');
                $table->string('ssc_roll', 6);
                $table->string('registration_number', 10);
                $table->string('passing_year', 4)->index();
                $table->decimal('gpa', 3, 2);
                $table->string('photo_path')->nullable();
                
                $table->string('name', 30);
                $table->enum('gender', ['Male', 'Female']);
                $table->string('mobile', 11);
                $table->string('email')->index();
                
                $table->string('father_name', 30);
                $table->string('mother_name', 30);
                $table->string('guardian_name', 30);
                $table->string('relation_with_guardian', 30);
                $table->string('guardian_mobile', 11)->index();
                $table->text('present_address');
                
                $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'waitlisted'])->default('pending');
                $table->string('pdf_path')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['status', 'department']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
