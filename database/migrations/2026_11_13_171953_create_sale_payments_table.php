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
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();
            $table->foreignId('cash_session_id')->constrained();
            $table->foreignId('payment_method_id')->constrained();
            $table->foreignId('layaway_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('payment_kind', 30)->default('new_payment');
            $table->decimal('amount', 14, 2);
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users');
            $table->string('reference', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
