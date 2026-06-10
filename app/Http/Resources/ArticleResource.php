<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'titre' => $this->titre,
            'slug' => $this->slug,
            'description' => $this->description,
            'prix' => (float) $this->prix,
            'currency' => $this->currency,
            'stock' => $this->stock,
            'etat' => $this->etat,
            'annee' => $this->annee,
            'localisation' => $this->localisation,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'with_delivery' => $this->with_delivery,
            'delivery_prix' => $this->delivery_prix ? (float) $this->delivery_prix : null,
            'is_boosted' => $this->is_boosted,
            'is_verified' => $this->is_verified,
            'vue_count' => $this->vue_count,
            'view_count' => $this->view_count,
            'contact_count' => $this->contact_count,
            'last_viewed_at' => $this->last_viewed_at,
            'images' => ArticleImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
        ];
    }
}
