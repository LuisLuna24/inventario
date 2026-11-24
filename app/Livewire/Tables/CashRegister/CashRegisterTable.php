<?php

namespace App\Livewire\Tables\CashRegister;

use App\Models\CashRegister;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class CashRegisterTable extends PowerGridComponent
{
    public string $tableName = 'cashRegisterTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return CashRegister::query()->with('warehouse');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')

            ->add('name')
            ->add('warehouse_id', fn (CashRegister $model) => $model->warehouse?->name ?? 'Sin almacén')
           /** Example of custom column using a closure **/
            ->add('code_lower', fn (CashRegister $model) => strtolower($model->code))
            ->add('is_active_toggle', fn(CashRegister $model) => (int) $model->is_active);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Code', 'code')
                ->sortable()
                ->searchable(),
            Column::make('Name', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Almacén', 'warehouse_id'),
            
            Column::make('Estatus', 'is_active_toggle')
                ->toggleable(true, 'Activo', 'Inactivo'),

            Column::action('Action')
        ];
    }

    public function edit(int|string $rowId): void
    {
        $this->dispatch('edit-cash-register', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        $this->dispatch('delete-cash-register', rowId: $rowId);
    }

    public function onPluginUpdated(string $plugin, string $event, array $params): void
    {
        if ($plugin !== 'toggleable' || $event !== 'updated' || data_get($params, 'field') !== 'is_active_toggle') {
            return;
        }

        $warehouseId = data_get($params, 'id');
        $value = data_get($params, 'value');

        if (! is_scalar($warehouseId) || ! is_scalar($value) || ! in_array((string) $value, ['0', '1'], true)) {
            return;
        }

        $warehouse = CashRegister::findOrFail((string) $warehouseId);
        $warehouse->is_active = (string) $value === '1';
        $warehouse->save();

        Flux::toast(
            variant: 'success',
            text: $warehouse->is_active
                ? __('Caja activada correctamente.')
                : __('Caja desactivada correctamente.'),
        );
    }

    /** @return array<int, Button> */
    public function actions(CashRegister $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.cash-registers.components.actions'),
        ];
    }
}
