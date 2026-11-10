<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Records behind the Teacher 360° profile. teacher_id / user_id reference users.id. */
    public function up(): void
    {
        Schema::create('teacher_lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title');
            $table->text('objectives')->nullable();
            $table->date('planned_date')->nullable();
            $table->string('status', 20)->default('planned'); // planned, delivered, cancelled
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_syllabus_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('topic');
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('target_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('submitted_count')->default(0);
            $table->unsignedInteger('marked_count')->default(0);
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title');
            $table->unsignedInteger('question_count')->default(0);
            $table->string('status', 20)->default('draft'); // draft, submitted, approved, returned
            $table->date('deadline')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('guardian_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('guardian_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('purpose');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status', 20)->default('requested'); // requested, confirmed, completed, cancelled
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->string('period_label', 50)->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->string('status', 20); // present, late, absent, on_leave
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('leave_type', 30); // casual, sick, earned, maternity, other
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 1);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('year');
            $table->string('leave_type', 30);
            $table->decimal('days', 5, 1);
            $table->timestamps();
            $table->unique(['user_id', 'year', 'leave_type']);
        });

        Schema::create('staff_payslips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('period'); // first day of the salary month
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->json('breakdown')->nullable();
            $table->string('status', 20)->default('issued'); // draft, issued, paid
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'period']);
        });

        Schema::create('staff_appraisals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('period_label', 50);
            $table->string('reviewer_name')->nullable();
            $table->date('review_date')->nullable();
            $table->text('summary')->nullable();
            $table->text('strengths')->nullable();
            $table->text('development_areas')->nullable();
            $table->string('status', 20)->default('scheduled'); // scheduled, in_progress, completed
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_trainings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('title');
            $table->string('provider')->nullable();
            $table->string('category', 30)->default('training'); // training, professional_development, workshop, certification
            $table->boolean('is_mandatory')->default(false);
            $table->decimal('hours', 6, 1)->default(0);
            $table->date('start_date')->nullable();
            $table->date('completed_on')->nullable();
            $table->string('status', 20)->default('enrolled'); // enrolled, completed, cancelled
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_conduct_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 20); // warning, discipline, commendation
            $table->string('title');
            $table->text('details')->nullable();
            $table->string('issued_by')->nullable();
            $table->date('issued_on');
            $table->string('status', 20)->default('open'); // open, resolved, expired
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'staff_conduct_records', 'staff_trainings', 'staff_appraisals', 'staff_payslips',
            'staff_leave_entitlements', 'staff_leaves', 'staff_attendances', 'teacher_feedback',
            'guardian_appointments', 'teacher_exam_questions', 'teacher_assignments',
            'teacher_syllabus_topics', 'teacher_lesson_plans',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
