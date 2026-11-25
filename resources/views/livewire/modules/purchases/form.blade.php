<div class="space-y-4">
    {{-- Header superior --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
                        {{ $purchase ? 'Editar compra '.$purchase->folio : 'Nueva compra' }}
                    </h1>
                    <span class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        Borrador
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400">
                    No afecta el inventario hasta confirmar la recepción.
                </p>
            </div>
        </div>

        <x-w-button label="Volver" icon="arrow-left" flat :href="route('purchases.index')" wire:navigate />
    </div>

    <form wire:submit="submit">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
            {{-- PANEL IZQUIERDO: Catálogo de Productos estilo POS --}}
            <section class="lg:col-span-7 xl:col-span-8 rounded-xl border border-slate-200 bg-white p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-900">
                {{-- Encabezado y barra de búsqueda / filtro --}}
                <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 dark:border-zinc-800 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            Catálogo de productos
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-zinc-400">
                            Haz clic en una tarjeta para agregar; vuelve a tocarla para sumar 1 unidad.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="sm:w-44">
                            <label for="catalog-category" class="sr-only">Categoría</label>
                            <select
                                id="catalog-category"
                                wire:model.live="categoryId"
                                class="w-full cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 transition-colors focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:focus:border-zinc-500"
                            >
                                <option value="">Todas las categorías</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="relative flex-1 sm:w-64">
                            <label for="catalog-search" class="sr-only">Buscar producto</label>
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-zinc-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <input
                                id="catalog-search"
                                type="text"
                                wire:model.live.debounce.300ms="productSearch"
                                placeholder="Buscar por nombre, SKU o código..."
                                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-8 text-sm text-slate-900 placeholder-slate-400 transition-colors focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500 dark:focus:border-zinc-500"
                            />
                            @if ($productSearch !== '')
                                <button
                                    type="button"
                                    wire:click="$set('productSearch', '')"
                                    aria-label="Limpiar búsqueda"
                                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-2.5 text-slate-400 hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Leyenda de estados intuitiva --}}
                <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600 dark:text-zinc-400">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                        Seleccionado
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        Stock bajo
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-rose-600"></span>
                        Sin stock
                    </span>
                </div>

                {{-- Grid de tarjetas de productos --}}
                @if ($catalogProducts->isNotEmpty())
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($catalogProducts as $product)
                            @php
                                $cartLine = collect($lines)->firstWhere('product_id', $product->id);
                                $inCart = $cartLine !== null;
                                $cartQty = $inCart ? (float) ($cartLine['quantity'] ?? 0) : 0;
                                $cartQtyLabel = rtrim(rtrim(number_format($cartQty, 3, '.', ''), '0'), '.');

                                $stock = (float) ($product->total_stock ?? 0);
                                $stockLabel = rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.');
                                $minStock = (float) ($product->min_stock ?? 0);

                                $isOutOfStock = $stock <= 0;
                                $isLowStock = ! $isOutOfStock && $minStock > 0 && $stock <= $minStock;

                                $cardClasses = match (true) {
                                    $inCart => 'border-emerald-600 bg-emerald-50/70 ring-1 ring-emerald-600/20 dark:border-emerald-500 dark:bg-emerald-950/30 dark:ring-emerald-500/20',
                                    $isOutOfStock => 'border-rose-200 bg-rose-50/40 hover:border-rose-300 dark:border-rose-900/60 dark:bg-rose-950/20 dark:hover:border-rose-800',
                                    $isLowStock => 'border-amber-300 bg-amber-50/50 hover:border-amber-400 dark:border-amber-700/60 dark:bg-amber-950/20 dark:hover:border-amber-600',
                                    default => 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/70 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70',
                                };
                            @endphp

                            <button
                                type="button"
                                wire:key="catalog-product-{{ $product->id }}"
                                wire:click="selectProduct({{ $product->id }})"
                                class="group flex min-h-[124px] cursor-pointer flex-col justify-between rounded-xl border p-3.5 text-left transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 {{ $cardClasses }}"
                            >
                                <div class="w-full space-y-2">
                                    {{-- Fila superior: SKU + Badge de stock --}}
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="truncate rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-medium text-slate-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            {{ $product->sku ?: ($product->barcode ?: '#'.$product->id) }}
                                        </span>

                                        @if ($isOutOfStock)
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-rose-100 px-1.5 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                                </svg>
                                                Sin stock
                                            </span>
                                        @elseif ($isLowStock)
                                            <span class="inline-flex shrink-0 items-center gap-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z" />
                                                </svg>
                                                Bajo: {{ $stockLabel }}
                                            </span>
                                        @else
                                            <span class="inline-flex shrink-0 items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium tabular-nums text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                Stock: {{ $stockLabel }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Nombre del producto --}}
                                    <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 dark:text-zinc-100" title="{{ $product->name }}">
                                        {{ $product->name }}
                                    </p>
                                </div>

                                {{-- Pie de tarjeta: Costo + Indicador de selección --}}
                                <div class="mt-3 flex w-full items-end justify-between gap-2 pt-1">
                                    <div>
                                        <span class="block text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-zinc-500">
                                            Últ. costo
                                        </span>
                                        <span class="text-sm font-semibold tabular-nums text-slate-800 dark:text-zinc-200">
                                            ${{ number_format((float) $product->cost, 2) }}
                                        </span>
                                    </div>

                                    @if ($inCart)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2 py-1 text-xs font-semibold tabular-nums text-white dark:bg-emerald-600">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                            {{ $cartQtyLabel }}
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition-colors group-hover:bg-slate-200 group-hover:text-slate-900 dark:bg-zinc-800 dark:text-zinc-300 dark:group-hover:bg-zinc-700 dark:group-hover:text-white">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>

                    {{-- Paginación del catálogo --}}
                    @if ($catalogProducts->hasPages())
                        <div class="mt-4 border-t border-slate-100 pt-4 dark:border-zinc-800">
                            {{ $catalogProducts->links() }}
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 px-6 py-14 text-center dark:border-zinc-700">
                        <svg class="mb-3 h-10 w-10 text-slate-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <p class="text-sm font-medium text-slate-700 dark:text-zinc-300">
                            No se encontraron productos
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                            Intenta con otro término de búsqueda o cambia la categoría.
                        </p>
                        @if ($productSearch !== '' || $categoryId)
                            <button
                                type="button"
                                wire:click="$set('productSearch', ''); $set('categoryId', null)"
                                class="mt-3 cursor-pointer rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                Limpiar filtros
                            </button>
                        @endif
                    </div>
                @endif
            </section>

            {{-- PANEL DERECHO: Recepción de Compra (Sticky / Fijo) --}}
            <aside class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-4">
                <div class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:max-h-[calc(100vh-2rem)]">
                    {{-- Header del Ticket --}}
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/70 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/10 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                                </svg>
                            </span>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Recepción de Compra
                            </h2>
                        </div>
                        <span class="rounded-md bg-slate-200/80 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-700 dark:bg-zinc-700 dark:text-zinc-200">
                            {{ count($lines) }} {{ count($lines) === 1 ? 'ítem' : 'ítems' }}
                        </span>
                    </div>

                    {{-- Datos esenciales: Proveedor, Almacén y Colapsable --}}
                    <div class="space-y-3 border-b border-slate-200 p-4 dark:border-zinc-700">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <div>
                                <x-w-select
                                    label="Proveedor *"
                                    wire:model.live="supplierId"
                                    option-label="name"
                                    option-value="id"
                                    :async-data="route('api.catalogs.suppliers')"
                                    placeholder="Selecciona proveedor"
                                    searchable
                                    required
                                />
                            </div>
                            <div>
                                <x-w-select
                                    label="Almacén *"
                                    wire:model.live="warehouseId"
                                    option-label="name"
                                    option-value="id"
                                    :async-data="route('api.warehouses')"
                                    placeholder="Selecciona almacén"
                                    searchable
                                    required
                                />
                            </div>
                        </div>

                        {{-- Panel colapsable para Fecha, Orden de Compra y Observaciones --}}
                        <div x-data="{ openDetails: {{ ($purchaseOrderId || $observation !== '' || $errors->hasAny(['date', 'purchaseOrderId', 'observation'])) ? 'true' : 'false' }} }" class="pt-0.5">
                            <button
                                type="button"
                                x-on:click="openDetails = !openDetails"
                                class="flex w-full cursor-pointer items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                <span>Detalles adicionales (fecha, orden, notas)</span>
                                <svg
                                    class="h-4 w-4 transition-transform duration-150"
                                    x-bind:class="openDetails ? 'rotate-180' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="openDetails" x-cloak class="mt-3 space-y-3 rounded-lg border border-slate-200/80 bg-slate-50/50 p-3 dark:border-zinc-700/80 dark:bg-zinc-800/30">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <x-w-input type="date" label="Fecha" wire:model="date" required />
                                    <div>
                                        <label for="purchase-order-select" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Orden de compra
                                        </label>
                                        <select
                                            id="purchase-order-select"
                                            wire:model.live="purchaseOrderId"
                                            class="w-full rounded-lg border border-gray-300 bg-white px-2.5 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        >
                                            <option value="">Sin orden</option>
                                            @foreach ($purchaseOrders as $order)
                                                <option value="{{ $order->id }}">{{ $order->serie }}-{{ $order->correlative }}</option>
                                            @endforeach
                                        </select>
                                        @error('purchaseOrderId') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <x-w-textarea label="Observaciones" wire:model="observation" rows="2" placeholder="Notas internas de la compra..." />
                            </div>
                        </div>
                    </div>

                    {{-- Lista de productos agregados (scrollable) --}}
                    <div class="flex-1 space-y-2.5 overflow-y-auto p-4 lg:min-h-[200px]">
                        @error('lines')
                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                {{ $message }}
                            </div>
                        @enderror

                        @forelse ($lines as $index => $line)
                            @php
                                $lineSubtotal = (float) ($line['quantity'] ?: 0) * (float) ($line['unit_cost'] ?: 0);
                            @endphp
                            <div
                                wire:key="purchase-line-{{ $line['product_id'] }}"
                                class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 transition-colors dark:border-zinc-700/80 dark:bg-zinc-800/50"
                            >
                                {{-- Nombre + Eliminar --}}
                                <div class="mb-2 flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold leading-snug text-slate-900 dark:text-zinc-100">
                                        {{ $line['name'] }}
                                    </p>
                                    <button
                                        type="button"
                                        wire:click="removeLine({{ $index }})"
                                        aria-label="Quitar {{ $line['name'] }}"
                                        class="shrink-0 cursor-pointer rounded p-1 text-slate-400 transition-colors hover:bg-rose-100 hover:text-rose-600 dark:text-zinc-500 dark:hover:bg-rose-950/60 dark:hover:text-rose-400"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Controles de línea: Unidad, Stepper Cantidad, Costo, Subtotal --}}
                                <div class="grid grid-cols-12 items-end gap-2">
                                    {{-- Unidad --}}
                                    <div class="col-span-4">
                                        <label class="mb-0.5 block text-[11px] font-medium text-slate-500 dark:text-zinc-400">Unidad</label>
                                        <select
                                            wire:model.live="lines.{{ $index }}.unit_type"
                                            class="w-full cursor-pointer rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                        >
                                            <option value="piece">Pieza</option>
                                            <option value="package" @disabled(empty($line['units_per_package']))>Caja</option>
                                        </select>
                                    </div>

                                    {{-- Cantidad con stepper --}}
                                    <div class="col-span-4">
                                        <label class="mb-0.5 block text-[11px] font-medium text-slate-500 dark:text-zinc-400">Cantidad</label>
                                        <div class="flex items-center overflow-hidden rounded-md border border-slate-300 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                            <button
                                                type="button"
                                                wire:click="decrementLine({{ $index }})"
                                                aria-label="Disminuir cantidad"
                                                class="cursor-pointer px-2 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            >
                                                -
                                            </button>
                                            <input
                                                type="number"
                                                step="any"
                                                min="0.001"
                                                wire:model.live.debounce.300ms="lines.{{ $index }}.quantity"
                                                class="w-full border-0 bg-transparent px-1 py-1.5 text-center text-xs font-medium tabular-nums text-slate-900 focus:outline-none focus:ring-0 dark:text-zinc-100"
                                            />
                                            <button
                                                type="button"
                                                wire:click="incrementLine({{ $index }})"
                                                aria-label="Aumentar cantidad"
                                                class="cursor-pointer px-2 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Costo unitario --}}
                                    <div class="col-span-4">
                                        <label class="mb-0.5 block text-[11px] font-medium text-slate-500 dark:text-zinc-400">Costo u.</label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-xs text-slate-400 dark:text-zinc-500">$</span>
                                            <input
                                                type="number"
                                                step="0.0001"
                                                min="0"
                                                wire:model.live.debounce.300ms="lines.{{ $index }}.unit_cost"
                                                class="w-full rounded-md border border-slate-300 bg-white py-1.5 pl-5 pr-1.5 text-right text-xs font-medium tabular-nums text-slate-900 focus:border-slate-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- Errores de línea + info de caja + subtotal --}}
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <div class="text-[11px] text-slate-500 dark:text-zinc-400">
                                        @if (!empty($line['units_per_package']) && $line['unit_type'] === 'package')
                                            <span>{{ number_format((float) $line['units_per_package'], 3) }} pzas/caja</span>
                                        @endif
                                        @error("lines.$index.quantity") <span class="block text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                        @error("lines.$index.unit_cost") <span class="block text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                                    </div>
                                    <span class="font-semibold tabular-nums text-slate-800 dark:text-zinc-200">
                                        ${{ number_format($lineSubtotal, 2) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="mb-2.5 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-zinc-800 dark:text-zinc-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">
                                    Lista vacía
                                </p>
                                <p class="mt-0.5 max-w-[220px] text-xs text-slate-400 dark:text-zinc-500">
                                    Selecciona productos del catálogo para comenzar la compra.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Resumen + Pago Modal Trigger + Acciones --}}
                    <div class="border-t border-slate-200 bg-slate-50/90 p-4 dark:border-zinc-700 dark:bg-zinc-800/80">
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex items-center justify-between text-slate-600 dark:text-zinc-400">
                                <dt>Subtotal</dt>
                                <dd class="font-medium tabular-nums text-slate-800 dark:text-zinc-200">${{ number_format((float) $subtotal, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between text-slate-600 dark:text-zinc-400">
                                <dt>IVA 16%</dt>
                                <dd class="font-medium tabular-nums text-slate-800 dark:text-zinc-200">${{ number_format((float) $taxTotal, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-lg font-bold text-slate-900 dark:border-zinc-700 dark:text-white">
                                <dt>Total</dt>
                                <dd class="text-xl tabular-nums">${{ number_format((float) $total, 2) }}</dd>
                            </div>
                        </dl>

                        {{-- Botón para abrir modal de pago opcional --}}
                        <div class="mt-3">
                            <button
                                type="button"
                                wire:click="$set('showPaymentModal', true)"
                                class="flex w-full cursor-pointer items-center justify-between rounded-lg border px-3 py-2 text-xs font-medium transition-colors {{ $paymentAmount !== '' ? 'border-emerald-600/40 bg-emerald-50 text-emerald-800 hover:bg-emerald-100/70 dark:border-emerald-500/40 dark:bg-emerald-950/30 dark:text-emerald-300 dark:hover:bg-emerald-950/50' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                            >
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                    </svg>
                                    @if ($paymentAmount !== '')
                                        Pago registrado: <strong class="tabular-nums">${{ number_format((float) $paymentAmount, 2) }}</strong>
                                    @else
                                        Registrar pago (opcional)
                                    @endif
                                </span>
                                <span class="underline">{{ $paymentAmount !== '' ? 'Editar' : 'Agregar' }}</span>
                            </button>

                            @if ($errors->hasAny(['cashSessionId', 'paymentAmount', 'paymentReference']))
                                <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">
                                    Revisa los datos del pago en el modal.
                                </p>
                            @endif
                        </div>

                        {{-- Acciones principales --}}
                        <div class="mt-3 flex flex-col gap-2">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <svg wire:loading.remove wire:target="submit" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <svg wire:loading wire:target="submit" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>Guardar borrador</span>
                            </button>

                            <x-w-button label="Cancelar" flat class="w-full" :href="route('purchases.index')" wire:navigate />
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    {{-- Modal de Pago Opcional --}}
    <x-w-modal-card title="Registrar pago (opcional)" wire:model="showPaymentModal" max-width="md">
        <div class="space-y-4">
            <p class="text-xs text-slate-500 dark:text-zinc-400">
                Déjalo vacío si la compra todavía no se paga o se liquidará después.
            </p>

            <x-w-number
                label="Importe pagado"
                wire:model="paymentAmount"
                min="0.01"
                step="0.01"
                prefix="$"
                placeholder="0.00"
            />

            <div>
                <label for="payment-cash-session" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Sesión de caja
                </label>
                <select
                    id="payment-cash-session"
                    wire:model="cashSessionId"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                >
                    <option value="">Selecciona una sesión</option>
                    @foreach ($cashSessions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->cashRegister->name }} · {{ $session->business_date->format('d/m/Y') }}
                        </option>
                    @endforeach
                </select>
                @error('cashSessionId') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <x-w-input
                label="Referencia"
                wire:model="paymentReference"
                placeholder="Folio, transferencia, ticket..."
            />
        </div>

        <x-slot name="footer" class="flex justify-between gap-2">
            <div>
                @if ($paymentAmount !== '' || $cashSessionId || $paymentReference !== '')
                    <x-w-button flat negative label="Quitar pago" wire:click="clearPayment" />
                @endif
            </div>
            <x-w-button primary label="Aceptar" wire:click="$set('showPaymentModal', false)" />
        </x-slot>
    </x-w-modal-card>
</div>
