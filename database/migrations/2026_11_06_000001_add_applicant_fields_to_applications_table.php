<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('blood_group', 10)->nullable()->after('gender');
            $table->text('current_address')->nullable()->after('blood_group');
            $table->text('permanent_address')->nullable()->after('current_address');
            $table->string('previous_school', 255)->nullable()->after('permanent_address');
            $table->decimal('previous_gpa', 4, 2)->nullable()->after('previous_school');
            $table->text('qr_token')->nullable()->after('qr_token_hash');
            $table->timestamp('withdrawn_at')->nullable()->after('qr_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['blood_group', 'current_address', 'permanent_address', 'previous_school', 'previous_gpa', 'qr_token', 'withdrawn_at']);
        });
    }
};
