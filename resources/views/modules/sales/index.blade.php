<x-layouts::app :title="__('Ventas')">
    <x-card title="Ventas">
        <div class="mb-4 flex items-center justify-between"><p class="text-sm text-slate-600 dark:text-zinc-400">Consulta ventas, pagos y estado.</p><x-w-button label="Nueva venta" icon="plus" primary :href="route('sales.create')" wire:navigate /></div>
        <livewire:tables.sale.sale-table />
    </x-card>
</x-layouts::app>
