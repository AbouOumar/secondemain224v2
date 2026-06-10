<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';
    case CarteBancaire = 'carte_bancaire';
    case Portefeuille = 'portefeuille';
    case Djomy = 'djomy';
}
