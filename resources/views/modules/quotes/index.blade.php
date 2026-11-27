<x-layouts::app :title="__('Presupuestos')">
    <x-card title="Presupuestos">
        <div class="mb-4 flex items-center justify-between"><p class="text-sm text-slate-600 dark:text-zinc-400">Vigencia de 7 días. Los presupuestos no reservan existencias.</p><x-w-button label="Nuevo presupuesto" icon="plus" primary :href="route('quotes.create')" wire:navigate /></div>
        <livewire:tables.quote.quote-table />
    </x-card>
</x-layouts::app>
