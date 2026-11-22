<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('modules.catalogs.products.index');
    }

    public function create(): View
    {
        return view('modules.catalogs.products.form');
    }

    public function edit(Product $producto): View
    {
        return view('modules.catalogs.products.form', [
            'product' => $producto,
        ]);
    }
}
