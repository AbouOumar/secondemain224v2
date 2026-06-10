<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case EnAttente = 'en_attente';
    case Assignee = 'assignee';
    case Acceptee = 'acceptee';
    case EnCours = 'en_cours';
    case Effectuee = 'effectuee';
    case Annulee = 'annulee';
}
