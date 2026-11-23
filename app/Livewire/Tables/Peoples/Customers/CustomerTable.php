<?php

namespace App\Livewire\Tables\Peoples\Customers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CustomerTable extends PowerGridComponent
{
    public string $tableName = 'customerTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Customer::query()->with('identity:id,name');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('identity_name', fn (Customer $model) => $model->identity?->name ?? 'Sin identidad')
            ->add('document_number')

            /** Example of custom column using a closure **/
            ->add('document_number_lower', fn (Customer $model) => strtolower($model->document_number))

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
        $this->redirect(route('customers.edit', ['customer' => $rowId]));
    }

    public function actions(Customer $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.peoples.customers.components.actions'),
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
