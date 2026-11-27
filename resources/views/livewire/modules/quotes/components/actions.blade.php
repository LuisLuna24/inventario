<div class="flex items-center gap-2">
    @if ($row->status === \App\Models\Quote::STATUS_PENDING && ! $row->isExpired())
        <x-w-button label="Editar" icon="pencil" xs primary :href="route('quotes.edit', $row)" wire:navigate />
        <x-w-button label="Convertir" icon="arrow-right" xs positive :href="route('sales.create', $row)" wire:navigate />
    @endif
</div>
