<div class="flex items-center gap-2">
    <x-w-button label="Editar" icon="pencil" xs primary :href="route('customers.edit', $row)" wire:navigate />
    <x-w-button label="Eliminar" icon="trash" xs negative wire:click="delete({{ $row->id }})"
        wire:confirm="¿Seguro que deseas eliminar este documento de identidad?" />
</div>
