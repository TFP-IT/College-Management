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
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'admission_fee')) {
                $table->decimal('admission_fee', 10, 2)->nullable()->after('amount');
            }
        });

        // Backfill existing rows where type is admission_fee
        DB::table('payment_transactions')
            ->where('type', 'admission_fee')
            ->update([
                'admission_fee' => DB::raw('amount')
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'admission_fee')) {
                $table->dropColumn('admission_fee');
            }
        });
    }
};
