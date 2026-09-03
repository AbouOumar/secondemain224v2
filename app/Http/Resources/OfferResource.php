<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'article' => new ArticleResource($this->whenLoaded('article')),
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'seller' => new UserResource($this->whenLoaded('seller')),
            'parent_offer_id' => $this->parent_offer_id,
            'montant' => (float) $this->montant,
            'made_by' => $this->made_by,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at,
        ];
    }
}
