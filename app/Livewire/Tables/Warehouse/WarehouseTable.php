<?php

namespace App\Livewire\Tables\Warehouse;

use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class WarehouseTable extends PowerGridComponent
{
    public string $tableName = 'warehouseTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()
                ->showSearchInput(),
            'footer' => PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    /** @return Builder<Warehouse> */
    public function datasource(): Builder
    {
        return Warehouse::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')

            ->add('name_lower', fn (Warehouse $model) => strtolower($model->name))

            ->add('location')
            ->add('is_active_toggle', fn (Warehouse $model) => (int) $model->is_active)
            ->add('created_at_formatted', fn (Warehouse $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id'),
            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Ubicación', 'location')
                ->sortable()
                ->searchable(),

            Column::make('Estatus', 'is_active_toggle')
                ->toggleable(true, 'Activo', 'Inactivo'),

            Column::action('Acciones'),
        ];
    }

    public function edit(int|string $rowId): void
    {
        // La edición se gestiona en el componente padre.
        $this->dispatch('edit', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        // La edición se gestiona en el componente padre.
        $this->dispatch('delete', rowId: $rowId);
    }

    /** @param array<string, mixed> $params */
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

        $warehouse = Warehouse::findOrFail((string) $warehouseId);
        $warehouse->is_active = (string) $value === '1';
        $warehouse->save();

        Flux::toast(
            variant: 'success',
            text: $warehouse->is_active
                ? __('Almacén activado correctamente.')
                : __('Almacén desactivado correctamente.'),
        );
    }

    /** @return array<int, Button> */
    public function actions(Warehouse $row): array
    {
        return [
            Button::add('edit')
                ->view('livewire.modules.catalogs.warehouses.components.actions'),
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
