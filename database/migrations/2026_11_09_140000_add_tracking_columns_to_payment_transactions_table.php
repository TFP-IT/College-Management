<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'application_no')) {
                $table->string('application_no', 60)->nullable()->after('transaction_id')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'student_id')) {
                $table->string('student_id', 60)->nullable()->after('application_no')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'student_name')) {
                $table->string('student_name', 100)->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('payment_transactions', 'class_name')) {
                $table->string('class_name', 50)->nullable()->after('student_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['application_no', 'student_id', 'student_name', 'class_name']);
        });
    }
};

