<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'rider_id',
        'pickup_adresse',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_adresse',
        'delivery_latitude',
        'delivery_longitude',
        'prix',
        'status',
        'accepted_at',
        'picked_up_at',
        'completed_at',
        'tracking_json',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'tracking_json' => 'array',
            'accepted_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
