<?php

namespace App\Models;

use App\Enums\EscrowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Escrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'amount',
        'commission_amount',
        'seller_amount',
        'rider_amount',
        'status',
        'held_at',
        'released_at',
        'refunded_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => EscrowStatus::class,
            'held_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
