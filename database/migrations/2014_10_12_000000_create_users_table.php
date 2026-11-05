<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->integer('role_id')->nullable();
                $table->integer('school_role')->nullable();
                $table->integer('parent_id')->nullable();
                $table->integer('school_id')->nullable();
                $table->string('password');
                $table->string('code')->nullable();
                $table->longText('user_information')->nullable();
                $table->longText('student_info')->nullable();
                $table->longText('documents')->nullable();
                $table->integer('status')->nullable();
                $table->integer('department_id')->nullable();
                $table->string('designation')->nullable();
                $table->string('language')->nullable();
                $table->text('menu_permission')->nullable();
                $table->string('account_status')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
