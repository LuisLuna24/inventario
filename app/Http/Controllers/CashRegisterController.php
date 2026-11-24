<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;

class CashRegisterController extends Controller
{
    public function index()
    {
        return view('modules.cash-registers.index');
    }

    public function show(CashRegister $cashRegister)
    {
        return view('modules.cash-registers.show', compact('cashRegister'));
    }
}
