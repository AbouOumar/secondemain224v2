<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Page à ouvrir au clic, d'après les données jointes à la notification
     * (commande, livraison, offre, annonce, alerte). Null s'il n'y en a pas.
     */
    public function targetUrl(): ?string
    {
        $data = $this->data ?? [];
        $user = $this->user;

        if ($orderRef = ($data['order_reference'] ?? null)) {
            $order = Order::where('reference', $orderRef)->first();
        } elseif ($orderId = ($data['order_id'] ?? null)) {
            $order = Order::find($orderId);
        }

        if (isset($order) && $order) {
            if ($order->buyer_id === $this->user_id && $order->status->value === 'en_attente_paiement') {
                return route('payment.show', $order);
            }
            if ($slug = $order->article?->slug) {
                return route('articles.show', $slug);
            }
        }

        if (isset($data['delivery_id'])) {
            // Le livreur retrouve ses courses sur son tableau de bord ;
            // acheteur et vendeur suivent la livraison.
            if ($user?->role?->value === 'motard') {
                return route('motard.dashboard');
            }
            return route('deliveries.tracking', $data['delivery_id']);
        }

        // C'est là que le vendeur accepte, refuse ou contre-propose.
        if (isset($data['offer_id'])) {
            return route('profile.offers.index');
        }

        $articleId = $data['article_id'] ?? ($data['article_ids'][0] ?? null);
        if ($slug = $this->articleSlug($articleId)) {
            return route('articles.show', $slug);
        }

        if (isset($data['alert_id'])) {
            return route('profile.alerts.index');
        }

        return null;
    }

    private function articleSlug(mixed $articleId): ?string
    {
        return $articleId ? Article::whereKey($articleId)->value('slug') : null;
    }
}
