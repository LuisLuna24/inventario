<x-layouts::app :title="isset($product) ? __('Editar producto') : __('Nuevo producto')">
    @livewire('modules.catalogs.products.form', ['product' => $product ?? null])
</x-layouts::app>
