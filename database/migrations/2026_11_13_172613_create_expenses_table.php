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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('cash_session_id')->constrained();
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->timestamp('expense_at');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_path', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
