<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Identity;

class IdentityController extends Controller
{
    public function index()
    {
        return view('modules.peoples.identities.index');
    }

    public function options(Request $request): JsonResponse
    {
        $selected = $request->input('selected');

        $query = Identity::query()
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
