<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'article_id' => 'required|exists:articles,id',
            'with_delivery' => 'nullable|boolean',
            'delivery_adresse' => 'nullable|string|max:255',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
        ];
    }
}
