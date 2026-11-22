<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Identidades') }}</h1>
        <x-w-button label="Nueva identidad" icon="plus" primary wire:click="create" />
    </div>

    <livewire:tables.peoples.identities.identity-table />

    <x-w-modal-card :title="$identityBeingEdited ? __('Editar identidad') : __('Nueva identidad')" wire:model="modalForm">
        <div class="flex flex-col gap-y-4">
            <x-w-input label="Nombre" wire:model="name" required />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">
            <x-w-button flat label="Cancelar" wire:click="closeModal" />
            <x-w-button primary label="Guardar" wire:click="submit" />
        </x-slot>
    </x-w-modal-card>
</div>
