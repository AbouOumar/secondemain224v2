<?php

namespace App\Enums;

enum OfferStatus: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case Contree = 'contree';
    case Expiree = 'expiree';
    case Annulee = 'annulee';
}
