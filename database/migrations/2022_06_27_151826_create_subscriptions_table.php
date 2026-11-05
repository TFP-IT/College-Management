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
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->integer('package_id');
                $table->integer('school_id');
                $table->double('paid_amount', 8, 2);
                $table->string('payment_method');
                $table->longText('transaction_keys');
                $table->integer('expire_date');
                $table->string('studentLimit')->nullable();
                $table->integer('date_added');
                $table->integer('active');
                $table->integer('status');
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
        Schema::dropIfExists('subscriptions');
    }
};
