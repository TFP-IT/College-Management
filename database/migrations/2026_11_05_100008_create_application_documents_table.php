<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('application_documents')) {
            Schema::create('application_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('doc_type', 50); // BIRTH_CERT, PREVIOUS_TRANSCRIPT, PHOTO, NID, OTHER
                $table->string('object_key', 500);
                $table->string('file_name', 255);
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('size_bytes');
                $table->string('checksum_sha256', 64)->nullable();
                $table->string('status', 50)->default('PENDING_UPLOAD')->index(); // PENDING_UPLOAD, UPLOADED, VERIFIED, REJECTED
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
