<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function index(): View
    {
        return view('modules.catalogs.warehouses.index');
    }

    public function options(Request $request): JsonResponse
    {
        $selected = $request->input('selected');

        $query = Warehouse::query()
            ->select(['id', 'name'])
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
