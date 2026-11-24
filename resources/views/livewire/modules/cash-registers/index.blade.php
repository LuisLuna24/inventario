<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Cajas') }}</h1>
        <x-w-button label="Nueva caja" icon="plus" primary wire:click="create" />
    </div>

    <livewire:tables.cash-register.cash-register-table />

    <x-w-modal-card :title="$cashRegisterBeingEdited ? __('Editar caja') : __('Nueva caja')" wire:model="modalForm">
        <div class="flex flex-col gap-y-4">
            <x-w-input label="Nombre" wire:model="name" required />
            <x-w-currency label="Codigo" wire:model="code" required />
            <x-w-select label="Almacén" wire:model.live="warehouse_id" option-label="name" option-value="id"
                :async-data="route('api.warehouses')" placeholder="Selecciona un almacén" searchable required />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">
            <x-w-button flat label="Cancelar" wire:click="closeModal" />
            <x-w-button primary label="Guardar" wire:click="submit" />
        </x-slot>
    </x-w-modal-card>
</div>
