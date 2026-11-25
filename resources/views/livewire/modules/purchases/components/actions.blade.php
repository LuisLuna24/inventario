<div class="flex items-center gap-2">
    <x-w-button label="Ver" icon="eye" xs flat :href="route('purchases.show', $row)" wire:navigate />
    @if ($row->isDraft())
        <x-w-button label="Editar" icon="pencil" xs primary :href="route('purchases.edit', $row)" wire:navigate />
        <x-w-button label="Confirmar" icon="check" xs positive wire:click="confirm({{ $row->id }})"
            wire:confirm="¿Confirmar la compra y dar entrada al inventario? Esta acción no se puede revertir." />
    @endif
</div>
