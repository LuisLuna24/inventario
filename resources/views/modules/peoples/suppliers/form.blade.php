<x-layouts::app :title="__('Proveedores')">
        @livewire('modules.peoples.suppliers.form', ['supplierBeingEdited' => $supplierBeingEdited ?? null])
</x-layouts::app>
