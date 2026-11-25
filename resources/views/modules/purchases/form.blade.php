<x-layouts::app :title="isset($purchase) ? __('Editar compra') : __('Nueva compra')">
    @livewire('modules.purchases.form', ['purchase' => $purchase ?? null])
</x-layouts::app>
