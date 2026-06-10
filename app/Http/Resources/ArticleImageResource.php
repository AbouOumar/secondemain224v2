<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleImageResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'url' => asset('storage/'.$this->url),
            'ordre' => $this->ordre,
        ];
    }
}
