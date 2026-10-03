<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'order_id' => 'required|exists:orders,id',
            // Djomy regroupe Orange Money, MTN MoMo et la carte. Les autres passerelles ne sont que des
            // bouchons : les accepter renverrait une erreur 500 (ou, pire, un paiement jamais encaissé).
            'methode' => 'required|string|in:djomy',
            'payer_phone' => 'nullable|string|max:20',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ];
    }
}
