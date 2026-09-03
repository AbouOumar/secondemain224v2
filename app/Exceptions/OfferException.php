<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreur métier sur une négociation de prix (montant trop bas, action non
 * autorisée pour cet utilisateur à ce stade, etc.) — le message est destiné
 * à être affiché tel quel à l'utilisateur.
 */
class OfferException extends RuntimeException
{
}
