<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">{{ __('Almacenes') }}</h1>
        <x-w-button label="{{ __('Nuevo almacén') }}" icon="plus" primary wire:click="create" />
    </div>
    <livewire:tables.warehouse.warehouse-table />

    <x-w-modal-card :title="$warehouseBeingEdited ? __('Editar almacén') : __('Nuevo almacén')" wire:model="modalForm">
        <div class="flex flex-col gap-y-4">
            <x-w-input label="Nombre" wire:model="name" required />
            <x-w-input label="Ubicación" wire:model="location" />
            <x-w-toggle label="Activo" wire:model="isActive" />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">

            <div class="flex gap-x-4">
                <x-w-button flat label="Cancelar" wire:click="closeModal" />

                <x-w-button primary label="Guardar" wire:click="submit" />
            </div>
        </x-slot>
    </x-w-modal-card>

</div>
