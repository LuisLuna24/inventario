<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class WarehousesController extends Controller
{
    public function index(): View
    {
        return view('modules.catalogs.warehouses.index');
    }
}
