<?php

namespace App\Livewire\Tables\Peoples\Suppliers;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class SupplierTable extends PowerGridComponent
{
    public string $tableName = 'supplierTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Supplier::query()->with('identity:id,name');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('identity_name', fn (Supplier $model) => $model->identity?->name ?? 'Sin identidad')
            ->add('document_number')

            /** Example of custom column using a closure **/
            ->add('document_number_lower', fn (Supplier $model) => strtolower($model->document_number))

            ->add('name')
            ->add('email')
            ->add('phone');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Documento', 'identity_name'),
            Column::make('Número de documento', 'document_number')
                ->sortable()
                ->searchable(),

            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Correo', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Teléfono', 'phone')
                ->sortable()
                ->searchable(),

            Column::action('Action'),
        ];
    }

    #[On('edit')]
    public function edit($rowId): void
    {
        $this->redirect(route('suppliers.edit', ['supplier' => $rowId]));
    }

    public function actions(Supplier $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.peoples.suppliers.components.actions'),
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
