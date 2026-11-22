<?php

namespace App\Livewire\Tables\Peoples\Identities;

use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;

final class IdentityTable extends PowerGridComponent
{
    public string $tableName = 'identityTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Identity> */
    public function datasource(): Builder
    {
        return Identity::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Nombre', 'name')->sortable()->searchable(),
            Column::action('Acciones'),
        ];
    }


    public function edit(int|string $rowId): void
    {
        $this->dispatch('edit-identity', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        $this->dispatch('delete-identity', rowId: $rowId);
    }

    public function actions(Identity $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.peoples.identities.components.actions'),
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
