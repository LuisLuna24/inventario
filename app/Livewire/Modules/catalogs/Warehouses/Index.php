<?php

namespace App\Livewire\Modules\catalogs\Warehouses;

use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public ?Warehouse $warehouseBeingEdited = null;

    public string $name = '';

    public string $location = '';

    public bool $isActive = true;

    public bool $modalForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->modalForm = true;
    }

    // Abrir modal de editar.
    #[On('edit')]
    public function edit(int|string $rowId): void
    {
        $this->resetForm();
        $this->warehouseBeingEdited = Warehouse::findOrFail($rowId);
        $this->modalForm = true;

        $this->name = $this->warehouseBeingEdited->name;
        $this->location = $this->warehouseBeingEdited->location ?? '';
        $this->isActive = $this->warehouseBeingEdited->is_active;
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'isActive' => ['boolean'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                Warehouse::updateOrCreate(
                    ['id' => $this->warehouseBeingEdited?->id],
                    [
                        'name' => $validated['name'],
                        'location' => $validated['location'] ?: null,
                        'is_active' => $validated['isActive'],
                    ],
                );
            });

            Flux::toast(
                heading: 'Almacén '.($this->warehouseBeingEdited ? 'editado' : 'creado'),
                text: 'El almacén se ha guardado correctamente.',
                variant: 'success',
            );
            $this->resetForm();
            $this->modalForm = false;
            $this->dispatch('pg:eventRefresh-warehouseTable');
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'Lo sentimos, no se pudo guardar o editar el almacén.',
                variant: 'warning',
            );
        }
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalForm = false;
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'location', 'isActive', 'warehouseBeingEdited']);
        $this->resetValidation();
    }

    #[On('delete')]
    public function delete(int|string $rowId): void
    {
        $warehouse = Warehouse::findOrFail($rowId);

        if ($this->hasDependencies($warehouse->id)) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El almacén está relacionado con otros registros.',
                variant: 'warning',
            );

            return;
        }

        try {
            $warehouse->delete();
        } catch (Throwable) {
            Flux::toast(
                heading: 'No se puede eliminar',
                text: 'El almacén está relacionado con otros registros.',
                variant: 'warning',
            );

            return;
        }

        Flux::toast(
            heading: 'Almacén eliminado',
            text: 'El almacén se ha eliminado correctamente.',
            variant: 'success',
        );
        $this->dispatch('pg:eventRefresh-warehouseTable');
    }

    public function render(): View
    {
        return view('livewire.modules.catalogs.warehouses.index');
    }

    private function hasDependencies(int $warehouseId): bool
    {
        $references = [
            'users' => ['warehouse_id'],
            'cash_registers' => ['warehouse_id'],
            'stock_balances' => ['warehouse_id'],
            'quotes' => ['warehouse_id'],
            'layaways' => ['warehouse_id'],
            'sales' => ['warehouse_id'],
            'purchase_orders' => ['warehouse_id'],
            'purchases' => ['warehouse_id'],
            'sale_returns' => ['warehouse_id'],
            'transfers' => ['origin_warehouse_id', 'destination_warehouse_id'],
            'inventory_adjustments' => ['warehouse_id'],
            'inventories' => ['warehouse_id'],
            'expenses' => ['warehouse_id'],
            'delivery_notes' => ['warehouse_id'],
            'audit_logs' => ['warehouse_id'],
        ];

        foreach ($references as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $warehouseId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
