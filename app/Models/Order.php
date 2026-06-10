<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'buyer_id',
        'seller_id',
        'article_id',
        'prix_article',
        'with_delivery',
        'delivery_prix',
        'total',
        'status',
        'annule_raison',
    ];

    protected function casts(): array
    {
        return [
            'with_delivery' => 'boolean',
            'status' => OrderStatus::class,
        ];
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
