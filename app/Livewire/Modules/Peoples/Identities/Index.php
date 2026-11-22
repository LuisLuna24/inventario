<?php

namespace App\Livewire\Modules\Peoples\Identities;

use App\Models\Identity;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?Identity $identityBeingEdited = null;

    public string $name = '';

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    #[On('edit-identity')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->identityBeingEdited = Identity::findOrFail($rowId);
        $this->name = $this->identityBeingEdited->name;
        $this->modalForm = true;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                Identity::updateOrCreate(
                    ['id' => $this->identityBeingEdited?->id],
                    [
                        'name' => $validated['name'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Identidad ' . ($this->identityBeingEdited ? 'editada' : 'creada'),
                text: 'La identidad se ha guardado correctamente.',
                variant: 'success',
            );
            $this->closeModal();
            $this->dispatch('pg:eventRefresh-categoryTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar la identidad.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    #[On('delete-identity')]
    public function delete(int|string $rowId): void
    {
        $identity = Identity::findOrFail($rowId);

        if ($identity->customers()->exists() || $identity->suppliers()->exists()) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La identidad tiene clientes o proveedores relacionados.',
                variant: 'warning',
            );

            return;
        }

        try {
            $identity->delete();
            $this->dispatch('pg:eventRefresh-identityTable');

        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La identidad está relacionada con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Identidad eliminada',
            text: 'La identidad se ha eliminado correctamente.',
            variant: 'success',
        );
        
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'identityBeingEdited']);
        $this->resetValidation();
        $this->dispatch('pg:eventRefresh-identityTable');
    }

    public function render()
    {
        return view('livewire.modules.peoples.identities.index');
    }
}
