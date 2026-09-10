<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleImageResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            // Defensive: compressAndStore() currently always returns a
            // relative storage path, but don't double-prefix if that ever
            // changes (e.g. a future S3/CDN migration, or a data: URI).
            'url' => preg_match('#^(https?:)?//|^data:#', $this->url)
                ? $this->url
                : asset('storage/'.$this->url),
            'ordre' => $this->ordre,
        ];
    }
}
