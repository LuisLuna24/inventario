<div class="flex items-center gap-2">
    <x-w-button label="Ver" icon="eye" xs primary :href="route('cash-registers.show', $row)" />
    <x-w-button label="Eliminar" icon="trash" xs negative wire:click="delete({{ $row->id }})"
        wire:confirm="¿Seguro que deseas eliminar esta caja?" />
</div>
