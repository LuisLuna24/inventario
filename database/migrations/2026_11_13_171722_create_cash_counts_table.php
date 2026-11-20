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
        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained();
            $table->foreignId('counted_by')->constrained('users');
            $table->timestamp('counted_at');
            $table->decimal('denomination', 14, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 14, 2);
            $table->unique(['cash_session_id', 'denomination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
