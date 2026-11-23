<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Clientes') }}</h1>
        <x-w-button label="Nuevo cliente" icon="plus" primary href="{{ route('customers.create') }}" />
    </div>

    <livewire:tables.peoples.customers.customer-table />
</div>
