<?php

namespace App\Livewire\Modules\Purchases;

use App\Exceptions\ExcessPurchaseQuantityException;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Purchases\ReceivePurchase;
use DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class Index extends Component
{
    public bool $excessModal = false;

    public ?int $pendingPurchaseId = null;

    /** @var array<int, string> */
    public array $excessProducts = [];

    #[On('confirm-purchase')]
    public function confirmPurchase(int|string $rowId, bool $excessConfirmed = false): void
    {
        $purchase = Purchase::query()->findOrFail($rowId);
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        try {
            app(ReceivePurchase::class)->handle($purchase, $user, $excessConfirmed);
            $this->closeExcessModal();
            $this->dispatch('pg:eventRefresh-purchaseTable');
            Flux::toast(
                heading: 'Compra confirmada',
                text: 'La entrada de inventario se registró correctamente.',
                variant: 'success',
            );
        } catch (ExcessPurchaseQuantityException $exception) {
            $this->pendingPurchaseId = (int) $rowId;
            $this->excessProducts = $exception->products;
            $this->excessModal = true;
        } catch (Throwable $exception) {
            report($exception);
            Flux::toast(
                heading: 'No se pudo confirmar',
                text: $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'La compra no pudo aplicarse al inventario.',
                variant: 'warning',
            );
        }
    }

    public function confirmExcess(): void
    {
        if ($this->pendingPurchaseId !== null) {
            $this->confirmPurchase($this->pendingPurchaseId, true);
        }
    }

    public function closeExcessModal(): void
    {
        $this->excessModal = false;
        $this->pendingPurchaseId = null;
        $this->excessProducts = [];
    }

    public function render(): View
    {
        return view('livewire.modules.purchases.index');
    }
}
