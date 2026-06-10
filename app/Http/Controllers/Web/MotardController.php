<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Events\DeliveryAccepted;
use App\Events\DeliveryCompleted;
use Illuminate\Http\Request;

class MotardController extends Controller
{
    public function dashboard()
    {
        // Get pending deliveries (status: en_attente) for the motard to accept
        $pendingDeliveries = Delivery::where('status', 'en_attente')
            ->with(['order.article'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get current assigned deliveries (status: acceptee or en_cours) for the motard
        $currentDeliveries = Delivery::where('rider_id', auth()->id())
            ->whereIn('status', ['acceptee', 'en_cours'])
            ->with(['order.article'])
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get delivery history (status: effectuee) for the motard
        $historyDeliveries = Delivery::where('rider_id', auth()->id())
            ->where('status', 'effectuee')
            ->with(['order.article'])
            ->orderBy('completed_at', 'desc')
            ->paginate(10);

        // Earnings for motard
        $userId = auth()->id();
        $now = now();
        $base = Delivery::where('rider_id', $userId)->where('status', 'effectuee');

        $earnings = [
            'aujourd_hui' => (clone $base)->whereDate('completed_at', $now->today())->sum('prix'),
            'cette_semaine' => (clone $base)->whereBetween('completed_at', [$now->startOfWeek(), $now->copy()->endOfWeek()])->sum('prix'),
            'ce_mois' => (clone $base)->whereMonth('completed_at', $now->month)->whereYear('completed_at', $now->year)->sum('prix'),
            'cette_annee' => (clone $base)->whereYear('completed_at', $now->year)->sum('prix'),
        ];

        return view('motard.dashboard', compact(
            'pendingDeliveries',
            'currentDeliveries',
            'historyDeliveries',
            'earnings'
        ));
    }

    public function setStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:en_ligne,occupe,hors_ligne',
        ]);

        auth()->user()->update(['rider_status' => $request->status]);

        return response()->json(['success' => true]);
    }

    public function accept($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->update([
            'rider_id' => auth()->id(),
            'status' => 'acceptee',
            'accepted_at' => now(),
        ]);
        event(new DeliveryAccepted($delivery));
        return redirect()->back()->with('success', 'Livraison acceptée.');
    }

    public function pickup($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->update([
            'status' => 'en_cours',
            'picked_up_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Colis récupéré.');
    }

    public function complete($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->update([
            'status' => 'effectuee',
            'completed_at' => now(),
        ]);
        event(new DeliveryCompleted($delivery));
        return redirect()->back()->with('success', 'Livraison terminée.');
    }
}