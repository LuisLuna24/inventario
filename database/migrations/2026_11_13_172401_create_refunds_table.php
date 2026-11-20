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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_exchange_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('layaway_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cash_session_id')->constrained();
            $table->decimal('amount', 14, 2);
            $table->timestamp('refunded_at');
            $table->foreignId('refunded_by')->constrained('users');
            $table->foreignId('authorized_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
