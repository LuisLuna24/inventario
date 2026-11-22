<?php

namespace App\Livewire\Modules\catalogs\Categories;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?Category $categoryBeingEdited = null;

    public string $name = '';

    public string $description = '';

    public int|string $porcent = 0;

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    #[On('edit-category')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->categoryBeingEdited = Category::findOrFail($rowId);
        $this->name = $this->categoryBeingEdited->name;
        $this->description = $this->categoryBeingEdited->description ?? '';
        $this->porcent = $this->categoryBeingEdited->porcent;
        $this->modalForm = true;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'porcent' => ['required', 'integer', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                Category::updateOrCreate(
                    ['id' => $this->categoryBeingEdited?->id],
                    [
                        'name' => $validated['name'],
                        'description' => $validated['description'] ?: null,
                        'porcent' => $validated['porcent'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Categoría '.($this->categoryBeingEdited ? 'editada' : 'creada'),
                text: 'La categoría se ha guardado correctamente.',
                variant: 'success',
            );
            $this->closeModal();
            $this->dispatch('pg:eventRefresh-categoryTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar la categoría.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    #[On('delete-category')]
    public function delete(int|string $rowId): void
    {
        $category = Category::findOrFail($rowId);

        if ($category->products()->exists()) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La categoría tiene productos relacionados.',
                variant: 'warning',
            );

            return;
        }

        try {
            $category->delete();
        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La categoría está relacionada con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Categoría eliminada',
            text: 'La categoría se ha eliminado correctamente.',
            variant: 'success',
        );
        $this->dispatch('pg:eventRefresh-categoryTable');
    }

    public function render(): View
    {
        return view('livewire.modules.catalogs.categories.index');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'porcent', 'categoryBeingEdited']);
        $this->resetValidation();
    }
}
