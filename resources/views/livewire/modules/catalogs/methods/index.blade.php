<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Métodos de pago') }}</h1>
        <x-w-button label="Nuevo método" icon="plus" primary wire:click="create" />
    </div>

    <livewire:tables.method.method-table />

    <x-w-modal-card :title="$methodBeingEdited ? __('Editar método') : __('Nuevo método')" wire:model="modalForm">
        <div class="flex flex-col gap-y-4">
            <x-w-input label="Nombre" wire:model="name" required />
            <x-w-currency label="Codigo" wire:model="code" required />
            <x-w-checkbox left-label="¿Es efectivo?" wire:model="is_cash" />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">
            <x-w-button flat label="Cancelar" wire:click="closeModal" />
            <x-w-button primary label="Guardar" wire:click="submit" />
        </x-slot>
    </x-w-modal-card>
</div>