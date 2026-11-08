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
        DB::statement("ALTER TABLE admissions 
            MODIFY COLUMN ssc_board VARCHAR(255) NULL, 
            MODIFY COLUMN ssc_roll VARCHAR(20) NULL, 
            MODIFY COLUMN registration_number VARCHAR(20) NULL, 
            MODIFY COLUMN passing_year VARCHAR(10) NULL, 
            MODIFY COLUMN gpa DECIMAL(3,2) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};

