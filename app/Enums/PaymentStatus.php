<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case EnAttente = 'en_attente';
    case Succes = 'succes';
    case Echoue = 'echoue';
    case Annule = 'annule';
}
