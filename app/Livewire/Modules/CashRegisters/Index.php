<?php

namespace App\Livewire\Modules\CashRegisters;

use App\Models\CashRegister;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?CashRegister $cashRegisterBeingEdited = null;

    public string $name = '';

    public int $warehouse_id;

    public int $code = 0;

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    #[On('edit-cash-register')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->cashRegisterBeingEdited = CashRegister::findOrFail($rowId);
        $this->name = $this->cashRegisterBeingEdited->name;
        $this->code = $this->cashRegisterBeingEdited->code;
        $this->warehouse_id = $this->cashRegisterBeingEdited->warehouse_id;
        $this->modalForm = true;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cash_registers', 'name')
                    ->where('warehouse_id', $this->warehouse_id)
                    ->ignore($this->cashRegisterBeingEdited?->id),
            ],
            'code' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('cash_registers', 'code')
                    ->ignore($this->cashRegisterBeingEdited?->id),
            ],
            'warehouse_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                CashRegister::updateOrCreate(
                    ['id' => $this->cashRegisterBeingEdited?->id],
                    [
                        'name' => $validated['name'],
                        'code' => $validated['code'],
                        'warehouse_id' => $validated['warehouse_id'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Caja ' . ($this->cashRegisterBeingEdited ? 'editada' : 'creada'),
                text: 'La caja se ha guardado correctamente.',
                variant: 'success',
            );
            $this->closeModal();
            $this->dispatch('pg:eventRefresh-cashRegisterTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar la caja.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    #[On('delete-cash-register')]
    public function delete(int|string $rowId): void
    {
        $cashRegister = CashRegister::findOrFail($rowId);

        if ($cashRegister->products()->exists()) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La caja tiene productos relacionados.',
                variant: 'warning',
            );

            return;
        }

        try {
            $cashRegister->delete();
            $this->dispatch('pg:eventRefresh-cashRegisterTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'La caja está relacionada con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Caja eliminada',
            text: 'La caja se ha eliminado correctamente.',
            variant: 'success',
        );
        $this->dispatch('pg:eventRefresh-cashRegisterTable');
    }

    public function render()
    {
        return view('livewire.modules.cash-registers.index');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'code', 'warehouse_id', 'cashRegisterBeingEdited']);
        $this->resetValidation();
    }
}
