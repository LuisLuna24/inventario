<?php

namespace App\Services\Purchases;

use App\Models\Product;
use App\Models\PurchaseItem;
use DomainException;

class PurchaseTotals
{
    public const TAX_RATE = 0.16;

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{subtotal: float, tax_total: float, total: float, items: array<int, array<string, mixed>>}
     */
    public function calculate(array $lines): array
    {
        $products = Product::query()
            ->whereKey(collect($lines)->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $subtotal = 0.0;
        $taxTotal = 0.0;
        $items = [];

        foreach ($lines as $line) {
            $product = $products->get((int) $line['product_id']);

            if ($product === null) {
                throw new DomainException('Uno de los productos seleccionados ya no existe.');
            }

            $quantity = round((float) $line['quantity'], 3);
            $unitCost = round((float) $line['unit_cost'], 4);
            $unitType = (string) $line['unit_type'];

            if ($quantity <= 0 || $unitCost < 0) {
                throw new DomainException('Las cantidades y costos de la compra no son válidos.');
            }

            $unitsPerPackage = null;

            if ($unitType === PurchaseItem::UNIT_PACKAGE) {
                $unitsPerPackage = round((float) $product->units_package, 3);

                if ($unitsPerPackage <= 0) {
                    throw new DomainException("El producto {$product->name} no tiene unidades por caja configuradas.");
                }
            }

            $lineTotal = round($quantity * $unitCost, 2);
            $taxRate = $product->apply_iva ? self::TAX_RATE : 0.0;
            $taxAmount = round($lineTotal * $taxRate, 2);

            $subtotal += $lineTotal;
            $taxTotal += $taxAmount;
            $items[] = [
                'product_id' => $product->id,
                'unit_type' => $unitType,
                'units_per_package' => $unitsPerPackage,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);

        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => round($subtotal + $taxTotal, 2),
            'items' => $items,
        ];
    }
}
