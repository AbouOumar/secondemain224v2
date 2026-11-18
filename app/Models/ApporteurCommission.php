<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Part de l'apporteur sur la première vente d'un vendeur qu'il a recruté. */
class ApporteurCommission extends Model
{
    public const PAID = 'verse';
    public const NOT_PAID = 'non_verse';

    protected $fillable = ['apporteur_id', 'seller_id', 'order_id', 'commission_amount', 'share', 'amount', 'status', 'transaction_id'];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'integer',
            'amount' => 'integer',
            'share' => 'float',
        ];
    }

    public function apporteur()
    {
        return $this->belongsTo(Apporteur::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
