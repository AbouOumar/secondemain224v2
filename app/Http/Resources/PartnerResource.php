<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'nom_magasin' => $this->nom_magasin,
            'slug' => $this->slug,
            'adresse' => $this->adresse,
            'description' => $this->description,
            'logo' => $this->logo ? asset('storage/'.$this->logo) : null,
            'is_verified' => $this->is_verified,
        ];
    }
}
