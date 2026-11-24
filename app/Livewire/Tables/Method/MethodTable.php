<?php

namespace App\Livewire\Tables\Method;

use App\Models\PaymentMethod;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class MethodTable extends PowerGridComponent
{
    public string $tableName = 'methodTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return PaymentMethod::query();
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

            /** Example of custom column using a closure **/
            ->add('code_lower', fn(PaymentMethod $model) => strtolower($model->code))

            ->add('name')
            ->add('is_active_toggle', fn(PaymentMethod $model) => (int) $model->is_active);
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

            Column::make('Estatus', 'is_active_toggle')
                ->toggleable(true, 'Activo', 'Inactivo'),

            Column::action('Action')
        ];
    }

    public function edit(int|string $rowId): void
    {
        $this->dispatch('edit-method', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        $this->dispatch('delete-method', rowId: $rowId);
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

        $warehouse = PaymentMethod::findOrFail((string) $warehouseId);
        $warehouse->is_active = (string) $value === '1';
        $warehouse->save();

        Flux::toast(
            variant: 'success',
            text: $warehouse->is_active
                ? __('Método de pago activado correctamente.')
                : __('Método de pago desactivado correctamente.'),
        );
    }

    /** @return array<int, Button> */
    public function actions(PaymentMethod $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.catalogs.methods.components.actions'),
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
