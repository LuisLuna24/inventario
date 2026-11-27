<div class="flex items-center gap-2">
    @if ($row->status === \App\Models\Sale::STATUS_COMPLETED)
        <form method="POST" action="{{ route('sales.cancel', $row) }}" onsubmit="return confirm('¿Cancelar esta venta y revertir sus movimientos de inventario y caja?')">
            @csrf
            <x-w-button type="submit" label="Cancelar" icon="x-mark" xs negative />
        </form>
    @endif
</div>
