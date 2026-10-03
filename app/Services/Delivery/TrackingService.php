<?php
namespace App\Services\Delivery;
use App\Models\Delivery;

class TrackingService {
    /** Le tracé garde les points les plus récents : une position toutes les 15 s pendant des heures grossirait sans fin. */
    public const MAX_POINTS = 500;

    public function updatePosition(Delivery $delivery, float $latitude, float $longitude): void {
        $tracking = $delivery->tracking_json ?? [];
        $tracking[] = [
            'lat' => $latitude,
            'lng' => $longitude,
            'timestamp' => now()->toIso8601String()
        ];
        $delivery->update(['tracking_json' => array_slice($tracking, -self::MAX_POINTS)]);
    }

    public function getTrack(Delivery $delivery): array {
        return $delivery->tracking_json ?? [];
    }
}
