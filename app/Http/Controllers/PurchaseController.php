<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        return view('modules.purchases.index');
    }

    public function create(): View
    {
        return view('modules.purchases.form');
    }

    public function edit(Purchase $purchase): View
    {
        abort_unless($purchase->isDraft(), 409, 'La compra confirmada no puede editarse.');

        return view('modules.purchases.form', compact('purchase'));
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load([
            'supplier',
            'warehouse',
            'purchaseOrder',
            'items.product',
            'payments.cashSession.cashRegister',
            'confirmedBy',
        ]);

        return view('modules.purchases.show', compact('purchase'));
    }
}
