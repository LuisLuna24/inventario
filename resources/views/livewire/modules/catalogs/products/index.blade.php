<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Productos') }}</h1>
        <x-w-button label="Nuevo producto" icon="plus" primary wire:click="create" />
    </div>

    <livewire:tables.product.product-table />

    <x-w-modal-card :title="$productBeingEdited ? __('Editar producto') : __('Nuevo producto')" wire:model="modalForm" max-width="5xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-w-native-select label="Categoría" wire:model="categoryId" option-label="name" option-value="id"
                :options="$categoryOptions" placeholder="Selecciona una categoría" required />
            <x-w-native-select label="Proveedor" wire:model="supplierId" option-label="name" option-value="id"
                :options="$supplierOptions" placeholder="Sin proveedor" />
            <x-w-input label="Nombre" wire:model="name" required />
            <x-w-input label="SKU" wire:model="sku" />
            <x-w-input label="Código de barras" wire:model="barcode" />
            <x-w-number label="Precio" wire:model="price" min="0" step="0.01" required />
            <x-w-number label="Costo" wire:model="cost" min="0" step="0.0001" required />
            <x-w-number label="Costo por paquete" wire:model="costPackage" min="0" step="0.0001" />
            <x-w-number label="Unidades por paquete" wire:model="unitsPackage" min="0.001" step="0.001" />
            <x-w-number label="Stock mínimo" wire:model="minStock" min="0" step="0.001" required />
            <x-w-number label="Porcentaje" wire:model="porcent" min="0" required />
            <div class="md:col-span-2">
                <x-w-textarea label="Descripción" wire:model="description" />
            </div>
            <x-w-toggle label="Aplica IVA" wire:model="applyIva" />
            <x-w-toggle label="Permitir apartado" wire:model="isLayawayAllowed" />
            <x-w-toggle label="Permitir devolución" wire:model="isReturnable" />
            <x-w-toggle label="Activo" wire:model="isActive" />
        </div>

        <x-slot name="footer" class="flex justify-end gap-x-4">
            <x-w-button flat label="Cancelar" wire:click="closeModal" />
            <x-w-button primary label="Guardar" wire:click="submit" />
        </x-slot>
    </x-w-modal-card>
</div>
