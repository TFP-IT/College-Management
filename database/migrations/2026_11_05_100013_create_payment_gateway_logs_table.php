<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateway_logs')) {
            Schema::create('payment_gateway_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('payment_id')->nullable()->constrained('admission_payments')->nullOnDelete();
                $table->string('provider', 50);
                $table->enum('direction', ['REQUEST', 'CALLBACK', 'VERIFY']);
                $table->json('payload')->nullable();
                $table->boolean('signature_valid')->default(false);
                $table->integer('http_status')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['provider', 'direction']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
