<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shared records and approvals, plus the modules whose rules need their own tables. */
return new class extends Migration
{
    public function up(): void
    {
        // One row per request, case, booking or listing. Module rules live in config/campus_modules.php.
        Schema::create('campus_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('type', 40);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('title', 200);
            $table->string('status', 30);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'type', 'status']);
            $table->index(['type', 'student_id']);
            $table->index(['type', 'staff_id']);
        });

        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('subject_type', 40);
            $table->unsignedBigInteger('subject_id');
            $table->string('title', 200);
            $table->json('steps');
            $table->unsignedTinyInteger('step')->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('workflow_requests')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 20);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Every change to what an invoice asks for, with its authority and reason.
        Schema::create('fee_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_id')->index();
            $table->string('kind', 20);
            $table->integer('amount'); // negative reduces what is owed
            $table->string('reason', 300);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['invoice_id', 'source_type', 'source_id'], 'fee_adjustments_once');
        });

        Schema::table('student_fee_managers', function (Blueprint $table) {
            $table->date('due_on')->nullable();
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->index();
            $table->string('title', 160);
            $table->string('kind', 20);
            $table->string('instructions', 1000)->nullable();
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamps();
            $table->index(['class_id', 'section_id']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('type', 20);
            $table->string('prompt', 1000);
            $table->json('options')->nullable();
            $table->json('answer');
            $table->decimal('marks', 5, 2)->default(1);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->json('answers')->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('max_score', 7, 2);
            $table->unique(['quiz_id', 'student_id']);
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('isbn', 20)->nullable();
            $table->string('title', 200);
            $table->string('author', 160)->nullable();
            $table->string('category', 60)->nullable();
            $table->string('shelf', 40)->nullable();
            $table->unsignedSmallInteger('copies')->default(1);
            $table->timestamps();
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('school_id');
            $table->string('status', 20);
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->date('returned_on')->nullable();
            $table->unsignedInteger('fine')->default(0);
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status']);
        });

        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('title', 160);
            $table->string('position', 80);
            $table->string('description', 1000)->nullable();
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id');
            $table->string('statement', 1000)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['election_id', 'student_id']);
        });

        // Who has voted (once each). Deliberately holds nothing about the choice.
        Schema::create('election_voters', function (Blueprint $table) {
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id');
            $table->primary(['election_id', 'student_id']);
        });

        // The choices. No voter, no timestamp and a random key, so a ballot cannot be traced to a person.
        Schema::create('election_ballots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id')->index();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('member_id')->index(); // student or guardian
            $table->unsignedBigInteger('staff_id')->index();
            $table->string('topic', 160);
            $table->dateTime('last_message_at');
            $table->timestamps();
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['conversation_messages', 'conversations', 'election_ballots', 'election_voters', 'election_candidates', 'elections',
            'library_loans', 'library_books', 'quiz_attempts', 'quiz_questions', 'quizzes', 'fee_adjustments', 'workflow_actions',
            'workflow_requests', 'campus_records'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('student_fee_managers', fn (Blueprint $table) => $table->dropColumn('due_on'));
    }
};
