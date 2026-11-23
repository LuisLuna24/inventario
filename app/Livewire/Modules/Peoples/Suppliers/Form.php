<?php

namespace App\Livewire\Modules\Peoples\Suppliers;

use App\Models\Supplier;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class Form extends Component
{
    public ?Supplier $supplierBeingEdited = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?int $identityDocumentId = null;

    public ?string $documentNumber = null;

    public ?string $address = null;

    public function mount(?Supplier $supplierBeingEdited = null): void
    {
        $this->supplierBeingEdited = $supplierBeingEdited;

        if ($this->supplierBeingEdited !== null) {
            $this->name = $this->supplierBeingEdited->name;
            $this->email = $this->supplierBeingEdited->email;
            $this->phone = $this->supplierBeingEdited->phone;
            $this->identityDocumentId = $this->supplierBeingEdited->identity_id;
            $this->documentNumber = $this->supplierBeingEdited->document_number;
            $this->address = $this->supplierBeingEdited->address;
        }
    }

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'identityDocumentId' => 'required|exists:identities,id',
            'documentNumber' => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function (): void {
                Supplier::updateOrCreate(
                    ['id' => $this->supplierBeingEdited?->id],
                    [
                        'name' => $this->name,
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'identity_id' => $this->identityDocumentId,
                        'document_number' => $this->documentNumber,
                        'address' => $this->address,
                    ]
                );
            });

            $wasEditing = $this->supplierBeingEdited !== null;

            Flux::toast(
                heading: 'Proveedor '.($wasEditing ? 'editado' : 'creado'),
                text: 'El proveedor se ha guardado correctamente.',
                variant: 'success',
            );

            $this->redirectRoute('suppliers.index', navigate: true);
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar el proveedor. Inténtalo nuevamente.',
                variant: 'warning',
            );
        }
    }

    public function render()
    {
        return view('livewire.modules.peoples.suppliers.form');
    }
}
