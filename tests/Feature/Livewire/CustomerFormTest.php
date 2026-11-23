<?php

use App\Livewire\Modules\Peoples\Customers\Form as CustomerForm;
use App\Models\Customer;
use App\Models\Identity;
use App\Models\User;
use Livewire\Livewire;

test('the customer edit route loads the selected customer data', function () {
    $identity = Identity::create(['name' => 'RFC']);
    $customer = Customer::create([
        'identity_id' => $identity->id,
        'document_number' => 'ABC123',
        'name' => 'Cliente original',
        'email' => 'cliente@example.com',
        'phone' => '5551234567',
        'address' => 'Dirección original',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('customers.edit', ['customer' => $customer]))
        ->assertOk()
        ->assertSeeLivewire(CustomerForm::class)
        ->assertSee('Cliente original')
        ->assertSee('cliente@example.com')
        ->assertSee('5551234567')
        ->assertSee('ABC123');
});

test('a customer edit form loads and saves the identity relationship', function () {
    $identity = Identity::create(['name' => 'RFC']);
    $customer = Customer::create([
        'identity_id' => $identity->id,
        'document_number' => 'ABC123',
        'name' => 'Cliente original',
        'email' => 'cliente@example.com',
        'phone' => '5551234567',
        'address' => 'Dirección original',
    ]);

    Livewire::test(CustomerForm::class, ['customerBeingEdited' => $customer])
        ->assertSet('name', 'Cliente original')
        ->assertSet('identityDocumentId', $identity->id)
        ->assertSet('documentNumber', 'ABC123')
        ->assertSet('email', 'cliente@example.com')
        ->assertSet('phone', '5551234567')
        ->assertSet('address', 'Dirección original')
        ->set('name', 'Cliente actualizado')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('customers.index'));

    expect($customer->refresh()->name)->toBe('Cliente actualizado')
        ->and($customer->identity_id)->toBe($identity->id)
        ->and(Customer::count())->toBe(1);
});
