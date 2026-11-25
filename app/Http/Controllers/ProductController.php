<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function edit(Product $product): View
    {
        return view('modules.catalogs.products.form', [
            'product' => $product,
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $selected = $request->input('selected');
        $query = Product::query()
            ->select(['id', 'name', 'sku', 'barcode'])
            ->where('is_active', true)
            ->when($selected !== null && $selected !== '', function ($query) use ($selected): void {
                $query->whereKey($selected);
            }, function ($query) use ($request): void {
                $query->when($request->filled('search'), function ($query) use ($request): void {
                    $search = '%'.$request->string('search').'%';
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', $search)
                            ->orWhere('sku', 'like', $search)
                            ->orWhere('barcode', 'like', $search);
                    });
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name.($product->sku ? ' · '.$product->sku : ''),
            ]);

        return response()->json($query);
    }
}
