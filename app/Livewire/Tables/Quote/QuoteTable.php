<?php

namespace App\Livewire\Tables\Quote;

use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class QuoteTable extends PowerGridComponent
{
    public string $tableName = 'quoteTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput()->showToggleColumns(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Quote> */
    public function datasource(): Builder
    {
        return Quote::query()->with('customer:id,name')->where('warehouse_id', auth()->user()->warehouse_id);
    }

    public function relationSearch(): array
    {
        return ['customer' => ['name']];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('folio', fn (Quote $quote): string => $quote->folio)
            ->add('customer_name', fn (Quote $quote): string => $quote->customer?->name ?? 'Público en general')
            ->add('date_formatted', fn (Quote $quote): string => $quote->date->format('d/m/Y'))
            ->add('expires_formatted', fn (Quote $quote): string => $quote->expires_at?->format('d/m/Y') ?? '-')
            ->add('total_formatted', fn (Quote $quote): string => '$'.number_format((float) $quote->total, 2))
            ->add('status_label', fn (Quote $quote): string => match (true) {
                $quote->status === Quote::STATUS_CONVERTED => 'Convertido',
                $quote->status === Quote::STATUS_CANCELLED => 'Cancelado',
                $quote->isExpired() => 'Vencido',
                default => 'Pendiente',
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Folio', 'folio', 'correlative')->sortable()->searchable(),
            Column::make('Cliente', 'customer_name'),
            Column::make('Fecha', 'date_formatted', 'date')->sortable(),
            Column::make('Vence', 'expires_formatted', 'expires_at')->sortable(),
            Column::make('Total', 'total_formatted', 'total')->sortable(),
            Column::make('Estado', 'status_label', 'status')->sortable(),
            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', 'status')->dataSource(collect([
                ['id' => Quote::STATUS_PENDING, 'name' => 'Pendiente'],
                ['id' => Quote::STATUS_CONVERTED, 'name' => 'Convertido'],
                ['id' => Quote::STATUS_CANCELLED, 'name' => 'Cancelado'],
            ]))->optionValue('id')->optionLabel('name'),
            Filter::datepicker('date', 'date'),
        ];
    }

    /** @return array<int, Button> */
    public function actions(Quote $row): array
    {
        return [Button::add('actions')->view('livewire.modules.quotes.components.actions')];
    }
}
