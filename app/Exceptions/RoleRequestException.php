<?php

namespace App\Exceptions;

use RuntimeException;

/** Règle de demande de rôle non respectée ; le code est le statut HTTP à renvoyer. */
class RoleRequestException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
