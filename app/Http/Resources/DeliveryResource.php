<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'rider' => new UserResource($this->whenLoaded('rider')),
            'pickup_adresse' => $this->pickup_adresse,
            'pickup_latitude' => (float) $this->pickup_latitude,
            'pickup_longitude' => (float) $this->pickup_longitude,
            'delivery_adresse' => $this->delivery_adresse,
            'delivery_latitude' => (float) $this->delivery_latitude,
            'delivery_longitude' => (float) $this->delivery_longitude,
            'prix' => (float) $this->prix,
            'status' => $this->status,
            'accepted_at' => $this->accepted_at,
            'completed_at' => $this->completed_at,
            'tracking' => $this->tracking_json,
            'created_at' => $this->created_at,
        ];
    }
}
