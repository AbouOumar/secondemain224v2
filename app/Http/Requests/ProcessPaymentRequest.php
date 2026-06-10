<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'order_id' => 'required|exists:orders,id',
            'methode' => 'required|string|in:orange_money,mtn_momo,carte_bancaire,portefeuille,djomy',
            'payer_phone' => 'nullable|string|max:20',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ];
    }
}
