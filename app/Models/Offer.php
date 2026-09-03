<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'buyer_id',
        'seller_id',
        'parent_offer_id',
        'montant',
        'made_by',
        'status',
        'expires_at',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function parentOffer()
    {
        return $this->belongsTo(Offer::class, 'parent_offer_id');
    }

    public function counterOffers()
    {
        return $this->hasMany(Offer::class, 'parent_offer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * L'utilisateur qui doit répondre à cette offre (celui qui n'a *pas*
     * fait la dernière proposition).
     */
    public function recipient(): User
    {
        return $this->made_by === 'acheteur' ? $this->seller : $this->buyer;
    }

    /**
     * L'utilisateur qui a fait cette proposition.
     */
    public function proposer(): User
    {
        return $this->made_by === 'acheteur' ? $this->buyer : $this->seller;
    }
}
