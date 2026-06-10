<?php
namespace App\Providers;
use App\Events\OrderCreated;
use App\Events\DeliveryAssigned;
use App\Events\DeliveryAccepted;
use App\Events\DeliveryCompleted;
use App\Events\MessageSent;
use App\Events\PaymentReceived;
use App\Listeners\NotifyBuyerOfOrderConfirmation;
use App\Listeners\NotifySellerOfNewOrder;
use App\Listeners\NotifyNearbyRiders;
use App\Listeners\NotifyRiderOfAssignment;
use App\Listeners\NotifyBuyerOfDeliveryComplete;
use App\Listeners\UpdateRiderTracking;
use App\Listeners\NotifySellerOfDeliveryAccepted;
use App\Listeners\BroadcastMessage;
use App\Listeners\ProcessPaymentConfirmation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            NotifyBuyerOfOrderConfirmation::class,
            NotifySellerOfNewOrder::class,
            NotifyNearbyRiders::class,
        ],
        DeliveryAssigned::class => [
            NotifyRiderOfAssignment::class,
        ],
        DeliveryAccepted::class => [
            UpdateRiderTracking::class,
            NotifySellerOfDeliveryAccepted::class,
        ],
        DeliveryCompleted::class => [
            NotifyBuyerOfDeliveryComplete::class,
        ],
        MessageSent::class => [
            BroadcastMessage::class,
        ],
        PaymentReceived::class => [
            ProcessPaymentConfirmation::class,
        ],
    ];

    public function boot(): void {}
}
