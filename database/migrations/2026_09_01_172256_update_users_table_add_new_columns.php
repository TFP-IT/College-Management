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
            $table->string('student_id')->nullable()->after('id');
            $table->string('teacher_id')->nullable()->after('student_id');
            $table->string('phone_number')->nullable()->after('email');
            $table->enum('user_type', ['regular', 'temp'])->default('regular')->after('phone_number');
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
            $table->dropColumn(['student_id', 'teacher_id', 'phone_number', 'user_type']);
        });

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL;');
    }
};
