<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function index()
    {
        return view('modules.peoples.identities.index');
    }
}
