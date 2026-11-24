<?php

namespace App\Livewire\Tables\CashRegister;

use App\Models\CashRegister;
use App\Models\CashSession;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CashSessionTable extends PowerGridComponent
{
    public string $tableName = 'cashSessionTable';

    // Filtros del panel
    public string $filterStatus = '';
    public string $filterRegister = '';
    public ?string $filterDateFrom = null;
    public ?string $filterDateTo = null;

    public function setUp(): array
    {
        // Recuerda las columnas ocultas entre recargas (localStorage)
        $this->persist(['columns']);

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.tables.cash-register.cash-session-filters'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function updatedFilterStatus(): void   { $this->resetPage(); }
    public function updatedFilterRegister(): void { $this->resetPage(); }
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void   { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['filterStatus', 'filterRegister', 'filterDateFrom', 'filterDateTo']);
        $this->resetPage();
    }

    // Datos para los selects del panel
    public function statusOptions(): array
    {
        return CashSession::query()->select('status')->distinct()->orderBy('status')->pluck('status')->all();
    }

    public function registerOptions()
    {
        return CashRegister::query()->orderBy('name')->get(['id', 'name']);
    }

    public function activeFiltersCount(): int
    {
        return collect([$this->filterStatus, $this->filterRegister, $this->filterDateFrom, $this->filterDateTo])
            ->filter(fn($v) => filled($v))
            ->count();
    }

    public function datasource(): Builder
    {
        return CashSession::query()
            ->with(['openedBy:id,name', 'closedBy:id,name', 'cashRegister:id,name'])
            ->when($this->filterStatus, fn(Builder $q, $v) => $q->where('status', $v))
            ->when($this->filterRegister, fn(Builder $q, $v) => $q->where('cash_register_id', $v))
            ->when($this->filterDateFrom, fn(Builder $q, $v) => $q->whereDate('business_date', '>=', $v))
            ->when($this->filterDateTo, fn(Builder $q, $v) => $q->whereDate('business_date', '<=', $v))
            ->latest('business_date');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('business_date_formatted', fn(CashSession $model) => Carbon::parse($model->business_date)->format('d/m/Y'))
            ->add('cash_register_name', fn(CashSession $model) => $model->cashRegister?->name ?? '—')
            ->add('opened_by_name', fn(CashSession $model) => $model->openedBy?->name ?? '—')
            ->add('opened_at_formatted', fn(CashSession $model) => Carbon::parse($model->opened_at)->format('d/m/Y H:i'))
            ->add('opening_amount_formatted', fn(CashSession $model) => '$' . number_format($model->opening_amount, 2))
            ->add('status')
            ->add('closed_by_name', fn(CashSession $model) => $model->closedBy?->name ?? '—')
            ->add('closed_at_formatted', fn(CashSession $model) => $model->closed_at
                ? Carbon::parse($model->closed_at)->format('d/m/Y H:i')
                : '—')
            ->add('expected_cash_formatted', fn(CashSession $model) => '$' . number_format($model->expected_cash, 2))
            ->add('counted_cash_formatted', fn(CashSession $model) => '$' . number_format($model->counted_cash, 2))
            ->add('cash_difference_formatted', fn(CashSession $model) => '$' . number_format($model->cash_difference, 2));
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha', 'business_date_formatted', 'business_date')->sortable(),
            Column::make('Caja', 'cash_register_name'),
            Column::make('Abrió', 'opened_by_name'),
            Column::make('Apertura', 'opened_at_formatted', 'opened_at')->sortable(),
            Column::make('Fondo inicial', 'opening_amount_formatted', 'opening_amount')->sortable(),
            Column::make('Estado', 'status')->sortable()->searchable(),
            Column::make('Cerró', 'closed_by_name'),
            Column::make('Cierre', 'closed_at_formatted', 'closed_at')->sortable(),
            Column::make('Esperado', 'expected_cash_formatted', 'expected_cash')->sortable(),
            Column::make('Contado', 'counted_cash_formatted', 'counted_cash')->sortable(),
            Column::make('Diferencia', 'cash_difference_formatted', 'cash_difference')->sortable(),
            Column::action('Acciones'),
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert(' . $rowId . ')');
    }

    public function actions(CashSession $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit: ' . $row->id)
                ->id()
                ->class('pg-btn-white dark:ring-zinc-600 dark:border-zinc-600 dark:hover:bg-zinc-700 dark:ring-offset-zinc-800 dark:text-zinc-300 dark:bg-zinc-700')
                ->dispatch('edit', ['rowId' => $row->id])
        ];
    }
}