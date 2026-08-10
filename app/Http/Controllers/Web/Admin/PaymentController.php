<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'order.article']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('methode')) {
            $query->where('methode', $request->methode);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        $totalComplete = (clone $query)->where('status', 'succes')->sum('montant');

        return view('admin.payments.index', compact('payments', 'totalComplete'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'order.article', 'order.buyer', 'order.seller']);

        return view('admin.payments.show', compact('payment'));
    }
}
