<?php

namespace App\Livewire\Tables\Sale;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class SaleTable extends PowerGridComponent
{
    public string $tableName = 'saleTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput()->showToggleColumns(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Sale> */
    public function datasource(): Builder
    {
        return Sale::query()->with('customer:id,name')->where('warehouse_id', auth()->user()->warehouse_id);
    }

    public function relationSearch(): array
    {
        return ['customer' => ['name']];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('folio', fn (Sale $sale): string => $sale->folio)
            ->add('customer_name', fn (Sale $sale): string => $sale->customer?->name ?? 'Público en general')
            ->add('date_formatted', fn (Sale $sale): string => $sale->date->format('d/m/Y H:i'))
            ->add('total_formatted', fn (Sale $sale): string => '$'.number_format((float) $sale->total, 2))
            ->add('status_label', fn (Sale $sale): string => $sale->status === Sale::STATUS_CANCELLED ? 'Cancelada' : 'Completada');
    }

    public function columns(): array
    {
        return [
            Column::make('Folio', 'folio', 'correlative')->sortable()->searchable(),
            Column::make('Cliente', 'customer_name'),
            Column::make('Fecha', 'date_formatted', 'date')->sortable(),
            Column::make('Total', 'total_formatted', 'total')->sortable(),
            Column::make('Estado', 'status_label', 'status')->sortable(),
            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', 'status')->dataSource(collect([
                ['id' => Sale::STATUS_COMPLETED, 'name' => 'Completada'],
                ['id' => Sale::STATUS_CANCELLED, 'name' => 'Cancelada'],
            ]))->optionValue('id')->optionLabel('name'),
            Filter::datepicker('date', 'date'),
        ];
    }

    /** @return array<int, Button> */
    public function actions(Sale $row): array
    {
        return [Button::add('actions')->view('livewire.modules.sales.components.actions')];
    }
}
