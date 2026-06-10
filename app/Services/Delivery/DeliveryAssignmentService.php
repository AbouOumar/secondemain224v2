<?php
namespace App\Services\Delivery;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Services\Geolocation\DistanceService;

class DeliveryAssignmentService {
    public function __construct(private DistanceService $distanceService) {}

    public function findNearestAvailableRider(float $pickupLat, float $pickupLng, float $maxRadiusKm = 10): ?User {
        $riders = User::where('role', UserRole::Motard)
            ->where('status', 'actif')
            ->where('last_online_at', '>=', now()->subMinutes(5))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();
        $nearest = null;
        $minDistance = $maxRadiusKm;
        foreach ($riders as $rider) {
            $distance = $this->distanceService->haversine($pickupLat, $pickupLng, $rider->latitude, $rider->longitude);
            if ($distance <= $minDistance) {
                $minDistance = $distance;
                $nearest = $rider;
            }
        }
        return $nearest;
    }

    public function assignDelivery(Delivery $delivery): ?User {
        $rider = $this->findNearestAvailableRider($delivery->pickup_latitude, $delivery->pickup_longitude);
        if ($rider) {
            $delivery->update(['rider_id' => $rider->id, 'status' => DeliveryStatus::Assignee]);
        }
        return $rider;
    }
}
