<x-card>
    <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $customerBeingEdited ? __('Editar cliente') : __('Nuevo cliente') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Completa la información comercial y de inventario. Los costos y el precio final se calculan
                automáticamente.
            </p>
        </div>

        <x-w-button label="Volver al catálogo" icon="arrow-left" flat :href="route('customers.index')" wire:navigate />
    </div>

    <form wire:submit="submit">
        <x-w-card>
            <x-w-input label="Nombre" wire:model="name" required />
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 my-3">
                <x-w-input label="Correo electrónico" wire:model="email" type="email" />
                <x-w-input label="Teléfono" wire:model="phone" />
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 my-3">
                <x-w-select label="Documento de identidad" wire:model="identityDocumentId" option-label="name"
                    option-value="id" :async-data="route('api.people.identities')" placeholder="Selecciona un documento de identidad" searchable />
                <x-w-input label="Numero de documento" wire:model="documentNumber" />
            </div>
            <x-w-input label="Dirección" wire:model="address" />

            <x-slot name="footer" class="flex items-center justify-end gap-3">
                <x-w-button label="Cancelar" flat negative  :href="route('customers.index')" wire:navigate />
                <x-w-button label="Guardar" icon="check-badge" primary type="submit" />
            </x-slot>
        </x-w-card>
    </form>
</x-card>
