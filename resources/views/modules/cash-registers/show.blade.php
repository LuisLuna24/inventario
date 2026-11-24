<x-layouts::app :title="isset($cashRegister) ? __('Editar caja') : __('Nueva caja')">
    @livewire('modules.cash-registers.show', ['cashRegister' => $cashRegister ?? null])
</x-layouts::app>
