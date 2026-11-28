<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 1-2 additions: notifications, learning, result publication, fee payments and the help desk. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('title', 160);
            $table->string('body', 500)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('lms_lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->index();
            $table->string('chapter', 120)->nullable();
            $table->string('title', 160);
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['class_id', 'section_id', 'status']);
        });

        Schema::create('lms_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->unique(['lesson_id', 'student_id']);
        });

        // The teacher profile already reads teacher_assignments, so assignments stay in that table.
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->unsignedInteger('total_marks')->default(10);
            $table->dateTime('due_at')->nullable();
            $table->boolean('allow_late')->default(true);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->decimal('marks', 7, 2)->nullable();
            $table->string('feedback', 1000)->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });

        // Marks are visible to families only after a row here says "published".
        Schema::create('result_publications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('exam_category_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->string('status', 20)->default('submitted');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();
            $table->unique(['session_id', 'exam_category_id', 'class_id', 'section_id'], 'result_publications_unique');
        });

        // Money received against an invoice. Rows are voided, never deleted.
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 120)->nullable();
            $table->string('receipt_no', 30)->nullable()->unique();
            $table->string('status', 20)->default('confirmed');
            $table->unsignedBigInteger('received_by')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();
        });

        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('category', 40);
            $table->string('subject', 160);
            $table->string('priority', 10)->default('normal');
            $table->string('status', 20)->default('open');
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });

        Schema::create('service_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('from_staff')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_ticket_messages');
        Schema::dropIfExists('service_tickets');
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('result_publications');
        Schema::dropIfExists('assignment_submissions');
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropColumn(['total_marks', 'due_at', 'allow_late']);
        });
        Schema::dropIfExists('lms_lesson_progress');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('campus_notifications');
    }
};
