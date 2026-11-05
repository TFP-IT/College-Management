<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** OTP contacts can now be an email address (guardians), which is longer than a phone number. */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('mobile', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('mobile', 20)->change();
        });
    }
};
