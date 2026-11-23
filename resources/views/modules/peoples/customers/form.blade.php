<x-layouts::app :title="__('Clientes')">
        @livewire('modules.peoples.customers.form', ['customerBeingEdited' => $customerBeingEdited ?? null])
</x-layouts::app>
