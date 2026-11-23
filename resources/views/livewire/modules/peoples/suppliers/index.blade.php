<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Proveedores') }}</h1>
        <x-w-button label="Nuevo proveedor" icon="plus" primary href="{{ route('suppliers.create') }}" />
    </div>

    <livewire:tables.peoples.suppliers.supplier-table />
</div>
