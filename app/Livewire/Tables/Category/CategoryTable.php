<?php

namespace App\Livewire\Tables\Category;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CategoryTable extends PowerGridComponent
{
    public string $tableName = 'categoryTable';

    public function setUp(): array
    {
        return [
            'header' => PowerGrid::header()->showSearchInput(),
            'footer' => PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    /** @return Builder<Category> */
    public function datasource(): Builder
    {
        return Category::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('description')
            ->add('porcent');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Nombre', 'name')->sortable()->searchable(),
            Column::make('Descripción', 'description')->searchable(),
            Column::make('Porcentaje', 'porcent')->sortable(),
            Column::action('Acciones'),
        ];
    }

    public function edit(int|string $rowId): void
    {
        $this->dispatch('edit-category', rowId: $rowId);
    }

    public function delete(int|string $rowId): void
    {
        $this->dispatch('delete-category', rowId: $rowId);
    }

    /** @return array<int, Button> */
    public function actions(Category $row): array
    {
        return [
            Button::add('actions')
                ->view('livewire.modules.catalogs.categories.components.actions'),
        ];
    }
}
