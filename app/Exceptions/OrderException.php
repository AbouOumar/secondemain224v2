<?php

namespace App\Exceptions;

use RuntimeException;

/** Règle métier de commande non respectée ; le code est le statut HTTP à renvoyer. */
class OrderException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
