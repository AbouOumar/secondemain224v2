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
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'avatar' => $this->avatar ? asset('storage/'.$this->avatar) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'last_online_at' => $this->last_online_at,
            'created_at' => $this->created_at,
        ];
    }
}
