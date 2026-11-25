<x-layouts::app :title="__('Compra :folio', ['folio' => $purchase->folio])">
    <x-card>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-950 dark:text-white">Compra {{ $purchase->folio }}</h1>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $purchase->isDraft() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $purchase->isDraft() ? 'Borrador' : 'Recibida' }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $purchase->date->format('d/m/Y') }} · {{ $purchase->supplier->name }}
                </p>
            </div>
            <div class="flex gap-2">
                <x-w-button label="Volver" icon="arrow-left" flat :href="route('purchases.index')" wire:navigate />
                @if ($purchase->isDraft())
                    <x-w-button label="Editar" icon="pencil" primary :href="route('purchases.edit', $purchase)" wire:navigate />
                @endif
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase text-gray-500">Proveedor</p>
                <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $purchase->supplier->name }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase text-gray-500">Almacén</p>
                <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $purchase->warehouse->name }}</p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase text-gray-500">Orden relacionada</p>
                <p class="mt-1 font-semibold text-gray-950 dark:text-white">
                    {{ $purchase->purchaseOrder ? $purchase->purchaseOrder->serie.'-'.$purchase->purchaseOrder->correlative : 'Sin orden' }}
                </p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                <p class="text-xs font-medium uppercase text-gray-500">Confirmación</p>
                <p class="mt-1 font-semibold text-gray-950 dark:text-white">
                    {{ $purchase->confirmed_at?->format('d/m/Y H:i') ?? 'Pendiente' }}
                </p>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Producto</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Unidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Cantidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Costo</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Subtotal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">IVA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach ($purchase->items as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $item->product->name }}</td>
                            <td class="px-4 py-3 text-right">{{ $item->unit_type === 'package' ? 'Caja' : 'Pieza' }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $item->unit_cost, 4) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $item->line_total, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">${{ number_format((float) $item->tax_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 ml-auto max-w-sm rounded-xl bg-gray-50 p-5 dark:bg-gray-800">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-medium tabular-nums">${{ number_format((float) $purchase->subtotal, 2) }}</dd></div>
                <div class="flex justify-between"><dt>IVA</dt><dd class="font-medium tabular-nums">${{ number_format((float) $purchase->tax_total, 2) }}</dd></div>
                <div class="flex justify-between border-t border-gray-200 pt-3 text-lg font-bold dark:border-gray-700"><dt>Total</dt><dd class="tabular-nums">${{ number_format((float) $purchase->total, 2) }}</dd></div>
            </dl>
        </div>

        @if ($purchase->payments->isNotEmpty())
            <section class="mt-6 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                <h2 class="font-semibold text-gray-950 dark:text-white">Pago registrado</h2>
                @foreach ($purchase->payments as $payment)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        ${{ number_format((float) $payment->amount, 2) }} · {{ $payment->cashSession->cashRegister->name }}
                        @if ($payment->reference) · Ref. {{ $payment->reference }} @endif
                    </p>
                @endforeach
            </section>
        @endif

        @if ($purchase->observation)
            <section class="mt-6 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                <h2 class="font-semibold text-gray-950 dark:text-white">Observaciones</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $purchase->observation }}</p>
            </section>
        @endif
    </x-card>
</x-layouts::app>
