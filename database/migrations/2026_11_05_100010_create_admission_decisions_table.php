<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admission_decisions')) {
            Schema::create('admission_decisions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('decision', 50); // APPROVED, WAITLISTED, REJECTED
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decided_at');
                $table->text('notes')->nullable();
                $table->integer('waitlist_rank')->nullable();
                $table->timestamps();

                $table->index(['application_id', 'decision']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_decisions');
    }
};
