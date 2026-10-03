<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Delivery;

class DeliveryPolicy
{
    public function view(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id || $user->id === $delivery->order->buyer_id || $user->role?->value === 'admin';
    }

    /**
     * Un livreur prend une course libre (en attente, sans livreur) ou confirme
     * une course que l'administration lui a assignée.
     */
    public function accept(User $user, Delivery $delivery): bool
    {
        if ($user->role?->value !== 'motard') {
            return false;
        }

        $status = $delivery->status->value;

        return ($delivery->rider_id === null && $status === 'en_attente')
            || ($delivery->rider_id === $user->id && in_array($status, ['en_attente', 'assignee'], true));
    }

    public function pickup(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id && $delivery->status->value === 'acceptee';
    }

    public function complete(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id && $delivery->status->value === 'en_cours';
    }

    /** Seul le livreur de la course, pendant qu'elle est active, envoie sa position. */
    public function track(User $user, Delivery $delivery): bool
    {
        return $user->id === $delivery->rider_id && in_array($delivery->status->value, ['acceptee', 'en_cours'], true);
    }

    public function confirm(User $user, Delivery $delivery): bool
    {
        return ($user->id === $delivery->order->buyer_id || $user->role?->value === 'admin')
            && $delivery->status->value === 'livree';
    }
}
