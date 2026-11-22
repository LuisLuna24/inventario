<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $product ? __('Editar producto') : __('Nuevo producto') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Completa la información comercial y de inventario. Los costos y el precio final se calculan
                automáticamente.
            </p>
        </div>

        <x-w-button label="Volver al catálogo" icon="arrow-left" flat :href="route('productos.index')" wire:navigate />
    </div>

    <form wire:submit="submit" class="space-y-6">
        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900 sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Información del producto</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Datos para identificarlo y localizarlo
                    rápidamente.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-w-input label="Nombre" wire:model="name" required />
                </div>
                <x-w-input label="SKU" wire:model="sku" />
                <x-w-input label="Código de barras" wire:model="barcode" />
                <x-w-select label="Categoría" wire:model.live="categoryId" option-label="name" option-value="id"
                    :async-data="route('api.catalogs.categories')" placeholder="Selecciona una categoría"
                    searchable required />
                <x-w-select label="Proveedor" wire:model="supplierId" option-label="name" option-value="id"
                    :async-data="route('api.catalogs.suppliers')" placeholder="Sin proveedor" searchable />
                <div class="md:col-span-2">
                    <x-w-textarea label="Descripción" wire:model="description" rows="4" />
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900 sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Costos y precio de venta</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Captura el paquete cuando aplique; el costo
                    unitario, IVA y precio se derivan en ese orden.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-w-number label="Costo por paquete" wire:model.live.debounce.300ms="costPackage" min="0"
                        step="0.01" hint="Opcional. Déjalo vacío para capturar el costo unitario." />
                    <x-w-number label="Unidades por paquete" wire:model.live.debounce.300ms="unitsPackage"
                        min="1" step="1" />
                    <x-w-number label="Costo unitario" wire:model.live.debounce.300ms="cost" min="0"
                        step="0.01" required
                        hint="Se calcula desde el paquete cuando ambos campos están completos." />
                    <x-w-number label="Margen sobre venta (%)" wire:model.live.debounce.300ms="porcent" min="0"
                        max="99" step="1" required disabled />
                    <div class="sm:col-span-2">
                        <x-w-toggle label="Aplicar IVA del 16% al costo" wire:model.live="applyIva" />
                    </div>
                </div>

                <aside class="rounded-xl bg-gray-50 p-5 dark:bg-gray-800" aria-label="Resumen de cálculo">
                    <h3 class="font-semibold text-gray-950 dark:text-white">Resumen del cálculo</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Costo unitario</dt>
                            <dd class="font-medium tabular-nums text-gray-950 dark:text-white">
                                ${{ number_format((float) $cost, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-600 dark:text-gray-400">Costo con IVA</dt>
                            <dd class="font-medium tabular-nums text-gray-950 dark:text-white">
                                ${{ number_format((float) $costIva, 4) }}</dd>
                        </div>
                        <div
                            class="flex items-center justify-between gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                            <dt class="font-medium text-gray-700 dark:text-gray-300">Precio final</dt>
                            <dd class="text-xl font-bold tabular-nums text-primary-600 dark:text-primary-400">
                                ${{ number_format((float) $price, 2) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs leading-5 text-gray-500 dark:text-gray-400">
                        Precio = costo con IVA ÷ (1 − margen). El precio guardado ya incluye IVA cuando corresponde.
                    </p>
                </aside>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900 sm:p-6">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Inventario y reglas comerciales</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Define el nivel mínimo y las operaciones
                    permitidas para este producto.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-w-number label="Stock mínimo" wire:model="minStock" min="0" step="0.001" required />
                <x-w-toggle label="Permitir apartado" wire:model="isLayawayAllowed" />
                <x-w-toggle label="Permitir devolución" wire:model="isReturnable" />
                <x-w-toggle label="Producto activo" wire:model="isActive" />
            </div>
        </section>

        <div
            class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 dark:border-gray-700 sm:flex-row sm:justify-end">
            <x-w-button label="Cancelar" flat :href="route('productos.index')" wire:navigate />
            <x-w-button label="Guardar producto" icon="check" primary type="submit" spinner="submit"
                wire:loading.attr="disabled" wire:target="submit" />
        </div>
    </form>
</div>
