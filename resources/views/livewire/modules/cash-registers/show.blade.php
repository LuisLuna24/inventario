<x-card>
    <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $cashRegister->name }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Código: {{ $cashRegister->code }} | Almacén: {{ $cashRegister->warehouse->name }}
            </p>
        </div>

        <x-w-button label="Volver al catálogo" icon="arrow-left" flat :href="route('cash-registers.index')" wire:navigate />
    </div>

    
    <livewire:tables.cash-register.cash-session-table />
</div>
