<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'montant' => (int) $this->montant,
            'phone' => $this->phone,
            'operator' => $this->operator,
            'status' => $this->status,
            'note' => $this->note,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
        ];
    }
}
