<?php

namespace App\Enums;

enum OrderStatus: string
{
    case EnAttentePaiement = 'en_attente_paiement';
    case Paye = 'paye';
    case EnCours = 'en_cours';
    case Livre = 'livre';
    case Annule = 'annule';
    case Refuse = 'refuse';
}
