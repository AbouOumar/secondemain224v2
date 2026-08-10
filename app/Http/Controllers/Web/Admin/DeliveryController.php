<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with(['order.article', 'rider']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.deliveries.index', compact('deliveries'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $request->validate(['status' => 'required|string|in:en_attente,assignee,acceptee,en_cours,livree,effectuee,annulee']);

        $delivery->update(['status' => $request->status]);

        return back()->with('success', 'Statut de la livraison mis à jour.');
    }
}
