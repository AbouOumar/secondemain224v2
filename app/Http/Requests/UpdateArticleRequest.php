<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'titre' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'prix' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'category_id' => 'nullable|exists:categories,id',
            'stock' => 'nullable|integer|min:0',
            'etat' => 'nullable|string|in:neuf,tres_bon,bon,moyen',
            'annee' => 'nullable|integer|min:1900|max:'.date('Y'),
            'localisation' => 'nullable|string|max:191',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'with_delivery' => 'nullable|boolean',
            'delivery_prix' => 'nullable|numeric|min:0',
            'images' => 'nullable|array|max:2',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
