<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Paiement = 'paiement';
    case Vente = 'vente';
    case Retrait = 'retrait';
    case Remboursement = 'remboursement';
    case Boost = 'boost';
}
