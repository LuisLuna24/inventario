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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('cost', 14, 4)->default(0);
            $table->boolean('apply_iva')->default(true);
            $table->decimal('cost_package', 14, 4)->nullable();
            $table->decimal('units_package', 14, 3)->nullable();
            $table->decimal('min_stock', 14, 3)->default(1);
            $table->integer('porcent')->default(0);
            $table->boolean('is_layaway_allowed')->default(false);
            $table->boolean('is_returnable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
