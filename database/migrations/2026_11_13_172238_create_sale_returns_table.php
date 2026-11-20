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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('return_reason_id')->constrained();
            $table->string('return_type', 20);
            $table->string('status', 25)->default('authorized');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('authorized_by')->constrained('users');
            $table->timestamp('authorized_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
