<?php

namespace App\Livewire\Modules\Catalogs\Methods;

use App\Models\PaymentMethod;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?PaymentMethod $methodBeingEdited = null;

    public string $name = '';

    public string $code = '';

    public bool $is_cash = false;

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    #[On('edit-method')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->methodBeingEdited = PaymentMethod::findOrFail($rowId);
        $this->name = $this->methodBeingEdited->name;
        $this->code = $this->methodBeingEdited->code ?? '';
        $this->is_cash = $this->methodBeingEdited->is_cash;
        $this->modalForm = true;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:255'],
            'is_cash' => ['required', 'boolean'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                PaymentMethod::updateOrCreate(
                    ['id' => $this->methodBeingEdited?->id],
                    [
                        'name' => $validated['name'],
                        'code' => $validated['code'] ?: null,
                        'is_cash' => $validated['is_cash'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Metodo de pago ' . ($this->methodBeingEdited ? 'editada' : 'creada'),
                text: 'El metodo de pago se ha guardado correctamente.',
                variant: 'success',
            );
            $this->closeModal();
            $this->dispatch('pg:eventRefresh-methodTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar el metodo de pago.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    #[On('delete-method')]
    public function delete(int|string $rowId): void
    {
        $method = PaymentMethod::findOrFail($rowId);

        if ($method->salePayments()->exists() || $method->layawayPayments()->exists() || $method->exchangePayments()->exists()) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El metodo de pago tiene transacciones relacionadas.',
                variant: 'warning',
            );

            return;
        }

        try {
            $method->delete();
            $this->dispatch('pg:eventRefresh-categoryTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El metodo de pago está relacionado con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Método de pago eliminado',
            text: 'El método de pago se ha eliminado correctamente.',
            variant: 'success',
        );
        $this->dispatch('pg:eventRefresh-methodTable');
    }

    public function render()
    {
        return view('livewire.modules.catalogs.methods.index');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'code', 'is_cash', 'methodBeingEdited']);
        $this->resetValidation();
    }
}
