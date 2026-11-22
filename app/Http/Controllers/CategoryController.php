<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('modules.catalogs.categories.index');
    }

    public function options(Request $request): JsonResponse
    {
        $selected = $request->input('selected');

        $query = Category::query()
            ->select(['id', 'name', 'porcent'])
            ->when($selected !== null && $selected !== '', function ($query) use ($selected): void {
                $query->whereKey($selected);
            }, function ($query) use ($request): void {
                $query->when($request->filled('search'), function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->string('search').'%');
                });
            })
            ->orderBy('name');

        return response()->json($query->limit(20)->get());
    }
}
