<?php
namespace App\Services\Delivery;
use App\Models\Delivery;

class TrackingService {
    public function updatePosition(Delivery $delivery, float $latitude, float $longitude): void {
        $tracking = $delivery->tracking_json ?? [];
        $tracking[] = [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now()->toIso8601String()
        ];
        $delivery->update(['tracking_json' => $tracking]);
    }

    public function getTrack(Delivery $delivery): array {
        return $delivery->tracking_json ?? [];
    }
}
