<?php

namespace App\Enums;

enum EscrowStatus: string
{
    case Retenu = 'retenu';
    case Libere = 'libere';
    case Rembourse = 'rembourse';
    case Litige = 'litige';
}
