<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->hasRealPhone() ? $this->phone : null,
            'role' => $this->role,
            // Disponibilité du livreur : visible uniquement par lui-même.
            'rider_status' => $this->when($request->user()?->id === $this->id, $this->rider_status),
            'status' => $this->status,
            'avatar' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'last_online_at' => $this->last_online_at,
            'created_at' => $this->created_at,
        ];
    }
}
