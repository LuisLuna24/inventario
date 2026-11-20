<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function index()
    {
        return view("modules.warehouses.warehouses.index");
    }

    public function create()
    {
        return view("modules.warehouses.warehouses.form");
    }

    public function edit($id)
    {
        return view("modules.warehouses.warehouses.form", compact("id"));
    }
}
