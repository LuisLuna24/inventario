<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('modules.peoples.customers.index');
    }

    public function create(): View
    {
        return view('modules.peoples.customers.form');
    }

    public function edit(Customer $customer): View
    {
        return view('modules.peoples.customers.form', [
            'customerBeingEdited' => $customer,
        ]);
    }
}
