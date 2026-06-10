<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'rater' => new UserResource($this->whenLoaded('rater')),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'role_type' => $this->role_type,
            'created_at' => $this->created_at,
        ];
    }
}
