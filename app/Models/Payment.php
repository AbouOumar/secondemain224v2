<?php

namespace App\Models;

use App\Enums\ArticleCurrency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'order_id',
        'user_id',
        'montant',
        'currency',
        'methode',
        'status',
        'external_ref',
        'external_data',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'currency' => ArticleCurrency::class,
            'methode' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'external_data' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
