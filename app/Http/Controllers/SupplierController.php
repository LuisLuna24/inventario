<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index()
    {
        return view('modules.peoples.suppliers.index');
    }

    public function options(Request $request): JsonResponse
    {
        $selected = $request->input('selected');

        $query = Supplier::query()
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
