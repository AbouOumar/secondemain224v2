<?php

namespace App\Enums;

enum NotificationType: string
{
    case AchatValide = 'achat_valide';
    case ProduitEnregistre = 'produit_enregistre';
    case LivraisonAssignee = 'livraison_assignee';
    case LivraisonAcceptee = 'livraison_acceptee';
    case LivraisonEffectuee = 'livraison_effectuee';
    case NouveauMessage = 'nouveau_message';
}
