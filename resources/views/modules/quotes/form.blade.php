<x-layouts::app :title="isset($quote) ? __('Editar presupuesto') : __('Nuevo presupuesto')">
    <x-card>
        @livewire('modules.quotes.form', ['quote' => $quote ?? null])
    </x-card>
</x-layouts::app>
