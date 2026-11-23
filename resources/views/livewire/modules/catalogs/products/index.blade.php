<div>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">{{ __('Productos') }}</h1>
        <x-w-button label="Nuevo producto" icon="plus" primary :href="route('products.create')" wire:navigate />
    </div>

    <livewire:tables.product.product-table />
</div>
