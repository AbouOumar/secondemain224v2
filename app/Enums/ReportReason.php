<?php

namespace App\Enums;

enum ReportReason: string
{
    case ContenuFrauduleux = 'contenu_frauduleux';
    case Arnaque = 'arnaque';
    case Contrefacon = 'contrefacon';
    case ArticleInterdit = 'article_interdit';
    case ContenuChoquant = 'contenu_choquant';
    case HarcelementSpam = 'harcelement_spam';
    case UsurpationIdentite = 'usurpation_identite';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ContenuFrauduleux => 'Contenu frauduleux',
            self::Arnaque => 'Tentative d\'arnaque',
            self::Contrefacon => 'Contrefaçon',
            self::ArticleInterdit => 'Article interdit à la vente',
            self::ContenuChoquant => 'Contenu choquant ou inapproprié',
            self::HarcelementSpam => 'Harcèlement ou spam',
            self::UsurpationIdentite => 'Usurpation d\'identité',
            self::Autre => 'Autre',
        };
    }
}
