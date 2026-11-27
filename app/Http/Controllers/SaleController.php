<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Sale;
use App\Services\Sales\CancelSale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use DomainException;
use Throwable;
use Flux\Flux;

class SaleController extends Controller
{
    public function index(): View
    {
        $sales = Sale::query()->with('customer:id,name')->where('warehouse_id', Auth::user()->warehouse_id)->latest('date')->paginate(20);
        return view('modules.sales.index', compact('sales'));
    }

    public function create(?Quote $quote = null): View
    {
        if ($quote !== null) abort_unless((int) $quote->warehouse_id === (int) Auth::user()->warehouse_id, 404);
        return view('modules.sales.form', compact('quote'));
    }

    public function cancel(Sale $sale, CancelSale $cancelSale)
    {
        abort_unless((int) $sale->warehouse_id === (int) Auth::user()->warehouse_id, 404);
        try {
            $cancelSale->handle($sale, Auth::user());
            Flux::toast(heading: 'Venta cancelada', text: 'Se revirtió el inventario y se registraron las salidas de caja.', variant: 'success');
        } catch (Throwable $exception) {
            report($exception);
            Flux::toast(heading: 'No se pudo cancelar', text: $exception instanceof DomainException ? $exception->getMessage() : 'Ocurrió un error al cancelar la venta.', variant: 'warning');
        }
        return Redirect::route('sales.index');
    }
}
