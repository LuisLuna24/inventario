<?php

namespace App\Livewire\Tables\Purchase;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class PurchaseTable extends PowerGridComponent
{
    public string $tableName = 'purchaseTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput()->showToggleColumns(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Purchase> */
    public function datasource(): Builder
    {
        return Purchase::query()->with([
            'supplier:id,name',
            'purchaseOrder:id,serie,correlative',
        ]);
    }

    public function relationSearch(): array
    {
        return [
            'supplier' => ['name'],
            'purchaseOrder' => ['serie', 'correlative'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('folio', fn (Purchase $purchase): string => $purchase->folio)
            ->add('supplier_id')
            ->add('supplier_name', fn (Purchase $purchase): string => $purchase->supplier->name)
            ->add('purchase_order_folio', fn (Purchase $purchase): string => $purchase->purchaseOrder === null
                ? 'Sin orden'
                : $purchase->purchaseOrder->serie.'-'.$purchase->purchaseOrder->correlative)
            ->add('date')
            ->add('date_formatted', fn (Purchase $purchase): string => $purchase->date->format('d/m/Y'))
            ->add('total_formatted', fn (Purchase $purchase): string => '$'.number_format((float) $purchase->total, 2))
            ->add('status')
            ->add('status_label', fn (Purchase $purchase): string => $purchase->isDraft() ? 'Borrador' : 'Recibida');
    }

    public function columns(): array
    {
        return [
            Column::make('Folio', 'folio', 'correlative')->sortable()->searchable(),
            Column::make('Proveedor', 'supplier_name', 'supplier_id')->sortable(),
            Column::make('Orden de compra', 'purchase_order_folio'),
            Column::make('Fecha', 'date_formatted', 'date')->sortable(),
            Column::make('Total', 'total_formatted', 'total')->sortable(),
            Column::make('Estado', 'status_label', 'status')->sortable(),
            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('supplier_id', 'supplier_id')
                ->dataSource(Supplier::query()->orderBy('name')->get(['id', 'name']))
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::select('status', 'status')
                ->dataSource(collect([
                    ['id' => Purchase::STATUS_DRAFT, 'name' => 'Borrador'],
                    ['id' => Purchase::STATUS_RECEIVED, 'name' => 'Recibida'],
                ]))
                ->optionValue('id')
                ->optionLabel('name'),
            Filter::datepicker('date', 'date'),
        ];
    }

    public function confirm(int|string $rowId): void
    {
        $this->dispatch('confirm-purchase', rowId: $rowId);
    }

    /** @return array<int, Button> */
    public function actions(Purchase $row): array
    {
        return [
            Button::add('actions')->view('livewire.modules.purchases.components.actions'),
        ];
    }
}
