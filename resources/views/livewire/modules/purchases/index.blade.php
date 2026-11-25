<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Compras</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Administra borradores y entradas confirmadas de inventario.</p>
        </div>
        <x-w-button label="Nueva compra" icon="plus" primary :href="route('purchases.create')" wire:navigate />
    </div>

    <livewire:tables.purchase.purchase-table />

    <x-w-modal-card title="Confirmar recepción con excedente" wire:model="excessModal">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Los siguientes productos superan la cantidad de la orden de compra:
        </p>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm font-medium text-amber-700 dark:text-amber-300">
            @foreach ($excessProducts as $product)
                <li>{{ $product }}</li>
            @endforeach
        </ul>
        <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">
            Al continuar se ingresará al inventario la cantidad capturada.
        </p>

        <x-slot name="footer" class="flex justify-end gap-3">
            <x-w-button label="Revisar compra" flat wire:click="closeExcessModal" />
            <x-w-button label="Confirmar excedente" warning wire:click="confirmExcess" spinner="confirmExcess" />
        </x-slot>
    </x-w-modal-card>
</div>
