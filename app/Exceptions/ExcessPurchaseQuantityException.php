<?php

namespace App\Exceptions;

use DomainException;

class ExcessPurchaseQuantityException extends DomainException
{
    /** @param array<int, string> $products */
    public function __construct(public readonly array $products)
    {
        parent::__construct('La compra contiene cantidades superiores a las ordenadas.');
    }
}
