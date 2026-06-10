<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'montant' => (float) $this->montant,
            'reference' => $this->reference,
            'source' => $this->source,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
