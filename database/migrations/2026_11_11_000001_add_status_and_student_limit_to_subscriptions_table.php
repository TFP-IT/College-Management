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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'studentLimit')) {
                $table->string('studentLimit')->nullable()->after('expire_date');
            }
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->integer('status')->default(1)->after('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'studentLimit')) {
                $table->dropColumn('studentLimit');
            }
            if (Schema::hasColumn('subscriptions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

