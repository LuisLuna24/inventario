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
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained();
            $table->foreignId('sale_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('exchange_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('layaway_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('purchase_payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('cash_movements')->nullOnDelete();
            $table->string('direction', 10);
            $table->string('movement_type', 35);
            $table->decimal('amount', 14, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['cash_session_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
