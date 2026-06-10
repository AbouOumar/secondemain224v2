<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id || $user->id === $order->seller_id || $user->role->value === 'admin';
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function pay(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id || $user->role->value === 'admin';
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->buyer_id && $order->status->value === 'en_attente_paiement';
    }
}
