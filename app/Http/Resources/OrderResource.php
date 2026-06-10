<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'buyer' => new UserResource($this->whenLoaded('buyer')),
            'seller' => new UserResource($this->whenLoaded('seller')),
            'article' => new ArticleResource($this->whenLoaded('article')),
            'prix_article' => (float) $this->prix_article,
            'with_delivery' => $this->with_delivery,
            'delivery_prix' => $this->delivery_prix ? (float) $this->delivery_prix : null,
            'total' => (float) $this->total,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
