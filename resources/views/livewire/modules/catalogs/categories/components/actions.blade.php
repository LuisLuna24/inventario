<div class="flex items-center gap-2">
    <x-w-button label="Editar" icon="pencil" xs primary wire:click="edit({{ $row->id }})" />
    <x-w-button label="Eliminar" icon="trash" xs negative wire:click="delete({{ $row->id }})"
        wire:confirm="¿Seguro que deseas eliminar esta categoría?" />
</div>
