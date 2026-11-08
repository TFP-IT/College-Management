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
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('guardian_nid', 20)->nullable()->after('guardian_mobile');
            $table->string('guardian_occupation', 50)->nullable()->after('guardian_nid');
            $table->string('class_name', 30)->nullable()->after('department');
            $table->string('birth_certificate_no', 30)->nullable()->after('name');
            $table->string('date_of_birth', 20)->nullable()->after('birth_certificate_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn(['guardian_nid', 'guardian_occupation', 'class_name', 'birth_certificate_no', 'date_of_birth']);
        });
    }
};
