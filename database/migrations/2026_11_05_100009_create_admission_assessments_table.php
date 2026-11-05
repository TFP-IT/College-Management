<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_assessments')) {
            Schema::create('admission_assessments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('assessment_type', 50); // WRITTEN, VIVA, INTERVIEW
                $table->dateTime('scheduled_at');
                $table->string('venue', 255)->nullable();
                $table->decimal('score', 5, 2)->nullable();
                $table->decimal('max_score', 5, 2)->default(100.00);
                $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'assessment_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_assessments');
    }
};
