<?php

namespace App\Enums;

enum UserStatus: string
{
    case Actif = 'actif';
    case Suspendu = 'suspendu';
    case EnAttente = 'en_attente';
}
