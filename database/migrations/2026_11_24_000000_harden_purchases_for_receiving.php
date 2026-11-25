<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table): void {
            $table->string('status', 25)->default('draft')->change();
            $table->decimal('subtotal', 14, 2)->default(0)->after('date');
            $table->decimal('tax_total', 14, 2)->default(0)->after('subtotal');
            $table->foreignId('confirmed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->unique(['serie', 'correlative']);
            $table->unique('purchase_order_id');
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->string('unit_type', 15)->default('piece')->after('product_id');
            $table->decimal('units_per_package', 14, 3)->nullable()->after('unit_type');
            $table->decimal('tax_rate', 5, 4)->default(0)->after('line_total');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
        });

        Schema::table('inventories', function (Blueprint $table): void {
            $table->unique('purchase_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropUnique(['purchase_item_id']);
        });

        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->dropColumn(['unit_type', 'units_per_package', 'tax_rate', 'tax_amount']);
        });

        Schema::table('purchases', function (Blueprint $table): void {
            $table->string('status', 25)->default('received')->change();
            $table->dropUnique(['purchase_order_id']);
            $table->dropUnique(['serie', 'correlative']);
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['subtotal', 'tax_total', 'confirmed_at']);
        });
    }
};
