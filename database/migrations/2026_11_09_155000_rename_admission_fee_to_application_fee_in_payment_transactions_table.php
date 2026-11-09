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
        // 1. Modify type column to varchar(50) so it supports 'application_fee'
        DB::statement("ALTER TABLE payment_transactions MODIFY type VARCHAR(50) NOT NULL DEFAULT 'application_fee'");

        // 2. Rename admission_fee column to application_fee
        if (Schema::hasColumn('payment_transactions', 'admission_fee')) {
            DB::statement("ALTER TABLE payment_transactions CHANGE admission_fee application_fee DECIMAL(10,2) NULL");
        } elseif (!Schema::hasColumn('payment_transactions', 'application_fee')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->decimal('application_fee', 10, 2)->nullable()->after('amount');
            });
        }

        // 3. Update existing rows where type was admission_fee
        DB::table('payment_transactions')
            ->where('type', 'admission_fee')
            ->update([
                'type' => 'application_fee',
                'application_fee' => DB::raw('amount')
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('payment_transactions', 'application_fee')) {
            DB::statement("ALTER TABLE payment_transactions CHANGE application_fee admission_fee DECIMAL(10,2) NULL");
        }
    }
};

