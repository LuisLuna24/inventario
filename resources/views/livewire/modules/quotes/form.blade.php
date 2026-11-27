<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ $quote ? 'Editar presupuesto '.$quote->folio : 'Nuevo presupuesto' }}</h1>
                <span class="rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">Pendiente</span>
            </div>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400">Vigencia de 7 días. Puedes incluir productos aunque no tengan existencias.</p>
        </div>
        <x-w-button label="Volver" icon="arrow-left" flat :href="route('quotes.index')" wire:navigate />
    </div>
    <form wire:submit="submit" class="grid grid-cols-1 gap-5 lg:grid-cols-12 lg:items-start">
        <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-7 xl:col-span-8">
            <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 dark:border-zinc-800 sm:flex-row">
                <div class="hidden sm:block sm:mr-auto"><h2 class="text-base font-semibold text-slate-900 dark:text-white">Catálogo de productos</h2><p class="text-xs text-slate-500 dark:text-zinc-400">Selecciona un producto para agregarlo al presupuesto.</p></div>
                <select wire:model.live="categoryId" class="rounded-lg border-slate-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-800"><option value="">Todas las categorías</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input wire:model.live.debounce.300ms="productSearch" placeholder="Buscar producto, SKU o código..." class="min-w-0 flex-1 rounded-lg border-slate-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-800">
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    <button type="button" wire:click="selectProduct({{ $product->id }})" class="group flex min-h-[124px] flex-col justify-between rounded-xl border border-slate-200 bg-white p-3.5 text-left transition hover:border-emerald-500 hover:bg-emerald-50/40 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/20">
                        <span class="block truncate text-sm font-semibold text-slate-800 dark:text-zinc-100">{{ $product->name }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ $product->sku ?: 'Sin SKU' }} · disp. {{ number_format(max((float) $product->available_stock - (float) $product->reserved_stock, 0), 3) }}</span>
                        <span class="mt-2 block text-sm font-bold text-emerald-700 dark:text-emerald-400">${{ number_format((float) $product->price, 2) }}{{ $product->apply_iva ? ' IVA incl.' : '' }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-4">{{ $products->links() }}</div>
        </section>
        <aside class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:sticky lg:top-4 lg:col-span-5 xl:col-span-4">
            <div class="space-y-3 p-4">
                <label class="block text-xs font-medium text-slate-600 dark:text-zinc-300">Cliente (opcional)
                    <select wire:model="customerId" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-800"><option value="">Público en general</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}{{ $customer->document_number ? ' · '.$customer->document_number : '' }}</option>@endforeach</select>
                </label>
                <label class="block text-xs font-medium text-slate-600 dark:text-zinc-300">Observaciones<textarea wire:model="observation" rows="2" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-800"></textarea></label>
            </div>
            <div class="max-h-[48vh] space-y-2 overflow-y-auto border-y border-slate-100 p-3 dark:border-zinc-800">
                @error('lines')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                @forelse($lines as $index => $line)
                    <div wire:key="quote-line-{{ $line['product_id'] }}" class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/50">
                        <div class="flex justify-between gap-2"><p class="text-sm font-semibold">{{ $line['name'] }}</p><button type="button" wire:click="removeLine({{ $index }})" class="text-rose-600">Quitar</button></div>
                        <div class="mt-2 grid grid-cols-2 gap-2"><label class="text-xs">Cantidad<input type="number" min="0.001" step="0.001" wire:model.live="lines.{{ $index }}.quantity" class="mt-1 w-full rounded border-slate-300 text-right text-sm dark:border-zinc-700 dark:bg-zinc-900"></label><label class="text-xs">Precio unitario<input type="text" readonly value="${{ number_format((float) $line['unit_price'], 2) }}" class="mt-1 w-full rounded border-slate-300 bg-slate-100 text-right text-sm dark:border-zinc-700 dark:bg-zinc-900"></label></div>
                        @error("lines.$index.quantity")<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                @empty <div class="py-8 text-center text-sm text-slate-500">Selecciona productos para comenzar.</div> @endforelse
            </div>
            <div class="space-y-3 bg-slate-50 p-4 dark:bg-zinc-800/70"><div class="flex justify-between text-lg font-bold"><span>Total</span><span>${{ number_format((float) $total, 2) }}</span></div><x-w-button type="submit" label="Guardar presupuesto" icon="check" primary class="w-full" /><x-w-button label="Cancelar" flat class="w-full" :href="route('quotes.index')" wire:navigate /></div>
        </aside>
    </form>
</div>
