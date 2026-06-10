<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request) {
        $query = Payment::with(['user', 'order']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('methode')) {
            $query->where('methode', $request->methode);
        }
        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment) {
        $payment->load(['user', 'order.article']);
        return new PaymentResource($payment);
    }
}
