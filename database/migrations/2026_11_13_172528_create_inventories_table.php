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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_return_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exchange_issued_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transfer_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('adjustment_item_id')->nullable()->constrained('inventory_adjustment_items')->nullOnDelete();
            $table->string('movement_type', 30);
            $table->string('detail', 255)->nullable();
            $table->decimal('quantity_in', 14, 3)->default(0);
            $table->decimal('quantity_out', 14, 3)->default(0);
            $table->decimal('cost_in', 14, 4)->default(0);
            $table->decimal('cost_out', 14, 4)->default(0);
            $table->decimal('total_in', 14, 2)->default(0);
            $table->decimal('total_out', 14, 2)->default(0);
            $table->decimal('quantity_balance', 14, 3);
            $table->decimal('cost_balance', 14, 4);
            $table->decimal('total_balance', 14, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();
            $table->index(['warehouse_id', 'product_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
