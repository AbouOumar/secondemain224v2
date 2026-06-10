<?php
namespace App\Listeners;
use App\Events\DeliveryAccepted;

class UpdateRiderTracking
{
    public function handle(DeliveryAccepted $event): void
    {
        $event->delivery->rider->update(['rider_status' => 'occupe']);
    }
}
