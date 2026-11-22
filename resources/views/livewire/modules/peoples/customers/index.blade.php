<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Clientes') }}</h1>
        <x-w-button label="Nuevo cliente" icon="plus" primary wire:click="create" />
    </div>

    <livewire:tables.peoples.customers.customer-table />

    <x-w-modal-card :title="$customerBeingEdited ? __('Editar cliente') : __('Nuevo cliente')" wire:model="modalForm">
        <div class="flex flex-col gap-y-4">
            <x-w-input label="Nombre" wire:model="name" required />
            <x-w-textarea label="Descripción" wire:model="description" />
            <x-w-number label="Porcentaje" wire:model="porcent" min="0" required />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">
            <x-w-button flat label="Cancelar" wire:click="closeModal" />
            <x-w-button primary label="Guardar" wire:click="submit" />
        </x-slot>
    </x-w-modal-card>
</div>
