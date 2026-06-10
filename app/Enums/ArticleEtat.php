<?php

namespace App\Enums;

enum ArticleEtat: string
{
    case Neuf = 'neuf';
    case TresBon = 'tres_bon';
    case Bon = 'bon';
    case Moyen = 'moyen';
}
