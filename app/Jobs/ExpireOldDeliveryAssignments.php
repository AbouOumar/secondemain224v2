<?php
namespace App\Jobs;
use App\Models\Delivery;
use App\Enums\DeliveryStatus;
use App\Services\Delivery\DeliveryAssignmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireOldDeliveryAssignments implements ShouldQueue
{
    use Queueable;

    public function handle(DeliveryAssignmentService $assignment): void
    {
        $expired = Delivery::where('status', DeliveryStatus::EnAttente)
            ->where('created_at', '<', now()->subMinutes(15))
            ->get();
        foreach ($expired as $delivery) {
            $assignment->assignDelivery($delivery);
        }
    }
}
