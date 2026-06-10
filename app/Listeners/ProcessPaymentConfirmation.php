<?php
namespace App\Listeners;
use App\Events\PaymentReceived;
use App\Services\Payment\PaymentProcessingService;

class ProcessPaymentConfirmation
{
    public function __construct(private PaymentProcessingService $paymentProcessing) {}

    public function handle(PaymentReceived $event): void
    {
        $this->paymentProcessing->confirmPayment($event->payment);
    }
}
