<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('mobile', 20)->index();
                $table->string('purpose', 30); // TRACK, LOGIN
                $table->string('code_hash', 255);
                $table->integer('attempts')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();

                $table->index(['mobile', 'purpose', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
