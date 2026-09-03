<?php
namespace App\Providers;
use App\Events\OrderCreated;
use App\Events\DeliveryAssigned;
use App\Events\DeliveryAccepted;
use App\Events\DeliveryDelivered;
use App\Events\DeliveryCompleted;
use App\Events\MessageSent;
use App\Events\PaymentReceived;
use App\Events\OfferMade;
use App\Events\OfferCountered;
use App\Events\OfferAccepted;
use App\Events\OfferRejected;
use App\Listeners\NotifyBuyerOfOrderConfirmation;
use App\Listeners\NotifySellerOfNewOrder;
use App\Listeners\NotifyNearbyRiders;
use App\Listeners\NotifyRiderOfAssignment;
use App\Listeners\NotifyBuyerOfDeliveryComplete;
use App\Listeners\NotifyBuyerOfDeliveryDelivered;
use App\Listeners\UpdateRiderTracking;
use App\Listeners\NotifySellerOfDeliveryAccepted;
use App\Listeners\BroadcastMessage;
use App\Listeners\ProcessPaymentConfirmation;
use App\Listeners\NotifySellerOfNewOffer;
use App\Listeners\NotifyOfferCountered;
use App\Listeners\NotifyOfferAccepted;
use App\Listeners\NotifyOfferRejected;
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
        DeliveryDelivered::class => [
            NotifyBuyerOfDeliveryDelivered::class,
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
        OfferMade::class => [
            NotifySellerOfNewOffer::class,
        ],
        OfferCountered::class => [
            NotifyOfferCountered::class,
        ],
        OfferAccepted::class => [
            NotifyOfferAccepted::class,
        ],
        OfferRejected::class => [
            NotifyOfferRejected::class,
        ],
    ];

    public function boot(): void {}
}
