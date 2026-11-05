<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('application_guardians')) {
            Schema::create('application_guardians', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('relation', 50);
                $table->string('mobile', 20)->index();
                $table->string('email', 255)->nullable();
                $table->string('occupation', 100)->nullable();
                $table->text('nid_passport')->nullable(); // encrypted cast
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_guardians');
    }
};
