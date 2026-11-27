<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class QuoteController extends Controller
{
    public function index(): View
    {
        $quotes = Quote::query()->with('customer:id,name')->where('warehouse_id', Auth::user()->warehouse_id)->latest('date')->paginate(20);
        return view('modules.quotes.index', compact('quotes'));
    }

    public function create(): View { return view('modules.quotes.form'); }

    public function edit(Quote $quote): View
    {
        abort_unless((int) $quote->warehouse_id === (int) Auth::user()->warehouse_id && $quote->status === Quote::STATUS_PENDING && ! $quote->isExpired(), 404);
        return view('modules.quotes.form', compact('quote'));
    }
}
