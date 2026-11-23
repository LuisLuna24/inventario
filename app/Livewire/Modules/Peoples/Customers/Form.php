<?php

namespace App\Livewire\Modules\Peoples\Customers;

use App\Models\Customer;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class Form extends Component
{
    public ?Customer $customerBeingEdited = null;

    public ?string $name = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?int $identityDocumentId = null;

    public ?string $documentNumber = null;

    public ?string $address = null;

    public function mount(?Customer $customerBeingEdited = null): void
    {
        $this->customerBeingEdited = $customerBeingEdited;

        if ($this->customerBeingEdited !== null) {
            $this->name = $this->customerBeingEdited->name;
            $this->email = $this->customerBeingEdited->email;
            $this->phone = $this->customerBeingEdited->phone;
            $this->identityDocumentId = $this->customerBeingEdited->identity_id;
            $this->documentNumber = $this->customerBeingEdited->document_number;
            $this->address = $this->customerBeingEdited->address;
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
                Customer::updateOrCreate(
                    ['id' => $this->customerBeingEdited?->id],
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

            $wasEditing = $this->customerBeingEdited !== null;

            Flux::toast(
                heading: 'Cliente '.($wasEditing ? 'editado' : 'creado'),
                text: 'El cliente se ha guardado correctamente.',
                variant: 'success',
            );

            $this->redirectRoute('customers.index', navigate: true);
        } catch (Throwable) {
            Flux::toast(
                heading: 'Ocurrió un error',
                text: 'No se pudo guardar el cliente. Inténtalo nuevamente.',
                variant: 'warning',
            );
        }
    }

    public function render()
    {
        return view('livewire.modules.peoples.customers.form');
    }
}
