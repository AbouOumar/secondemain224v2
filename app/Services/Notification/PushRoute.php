<?php

namespace App\Services\Notification;

use App\Models\Article;

/**
 * Page de l'app mobile à ouvrir au toucher d'une notification
 * (chemins définis par src/lib/routes.ts de secondmain-mobile).
 */
class PushRoute
{
    public const FALLBACK = '/profile/';

    public static function forNotification(string $type, array $data): string
    {
        $articleId = $data['article_id'] ?? ($data['article_ids'][0] ?? null);
        $slug = $articleId ? Article::whereKey($articleId)->value('slug') : null;

        return $slug ? '/article/?slug='.rawurlencode($slug) : self::FALLBACK;
    }

    public static function conversation(int $userId, ?int $articleId, string $name): string
    {
        return '/conversation/?'.http_build_query(array_filter([
            'user' => $userId,
            'article_id' => $articleId,
            'name' => $name,
        ]));
    }
}
