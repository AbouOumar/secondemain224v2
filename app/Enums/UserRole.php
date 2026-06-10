<?php

namespace App\Enums;

enum UserRole: string
{
    case Acheteur = 'acheteur';
    case Vendeur = 'vendeur';
    case RevendeurPro = 'revendeur_pro';
    case Motard = 'motard';
    case Admin = 'admin';
}
