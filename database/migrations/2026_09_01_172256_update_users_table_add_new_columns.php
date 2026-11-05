<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'teacher_id')) {
                $table->string('teacher_id')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['regular', 'temp'])->default('regular')->after('phone_number');
            }
        });
        
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach (['student_id', 'teacher_id', 'phone_number', 'user_type'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $cols[] = $c;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL;');
    }
};
