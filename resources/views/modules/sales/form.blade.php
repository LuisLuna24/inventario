<x-layouts::app :title="__('Nueva venta')">
    <x-card>
        @livewire('modules.sales.form', ['quote' => $quote ?? null])
    </x-card>
</x-layouts::app>
