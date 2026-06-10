<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Delivery;

class DeliveryPolicy
{
    public function view(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id || $user->id === $delivery->order->buyer_id || $user->role === 'admin';
    }

    public function accept(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id && $delivery->status === 'assignee';
    }

    public function complete(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id && $delivery->status === 'en_cours';
    }
}
