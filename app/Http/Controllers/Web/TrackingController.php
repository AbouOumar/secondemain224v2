<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\Delivery\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingController extends Controller
{
    public function __construct(private TrackingService $trackingService) {}

    public function motardTracking($id)
    {
        $delivery = Delivery::with(['order.article', 'order.buyer', 'order.seller'])->findOrFail($id);

        if ($delivery->rider_id !== Auth::id()) {
            abort(403, 'Cette livraison ne vous est pas attribuée.');
        }

        if (!in_array($delivery->status->value, ['acceptee', 'en_cours'])) {
            return redirect()->route('motard.dashboard')
                ->with('error', 'Cette livraison n\'est pas en cours.');
        }

        $track = $this->trackingService->getTrack($delivery);

        return view('motard.tracking', compact('delivery', 'track'));
    }

    public function updatePosition(Request $request, $id)
    {
        $delivery = Delivery::findOrFail($id);

        if ($delivery->rider_id !== Auth::id()) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->trackingService->updatePosition(
            $delivery,
            (float) $request->latitude,
            (float) $request->longitude
        );

        return response()->json(['success' => true]);
    }

    public function publicTracking($id)
    {
        $delivery = Delivery::with(['order.article.images', 'order.buyer', 'order.seller', 'rider'])
            ->findOrFail($id);

        $user = Auth::user();
        $isBuyer = $user && $delivery->order->buyer_id === $user->id;
        $isSeller = $user && $delivery->order->seller_id === $user->id;
        $isRider = $user && $delivery->rider_id === $user->id;
        $isAdmin = $user && $user->role?->value === 'admin';

        if (!($isBuyer || $isSeller || $isRider || $isAdmin)) {
            abort(403);
        }

        $track = $this->trackingService->getTrack($delivery);

        return view('deliveries.tracking', compact('delivery', 'track', 'isBuyer', 'isSeller', 'isRider'));
    }

    public function getTrack($id)
    {
        $delivery = Delivery::with('rider')->findOrFail($id);

        $user = Auth::user();
        $isBuyer = $user && $delivery->order->buyer_id === $user->id;
        $isSeller = $user && $delivery->order->seller_id === $user->id;
        $isRider = $user && $delivery->rider_id === $user->id;
        $isAdmin = $user && $user->role?->value === 'admin';

        if (!($isBuyer || $isSeller || $isRider || $isAdmin)) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $track = $this->trackingService->getTrack($delivery);
        $lastPosition = count($track) > 0 ? $track[count($track) - 1] : null;
        $riderPosition = $delivery->rider ? [
            'lat' => (float) $delivery->rider->latitude,
            'lng' => (float) $delivery->rider->longitude,
        ] : null;

        return response()->json([
            'track' => $track,
            'last_position' => $lastPosition,
            'rider_position' => $riderPosition,
            'status' => $delivery->status->value,
            'pickup' => [
                'lat' => (float) $delivery->pickup_latitude,
                'lng' => (float) $delivery->pickup_longitude,
                'address' => $delivery->pickup_adresse,
            ],
            'delivery' => [
                'lat' => (float) $delivery->delivery_latitude,
                'lng' => (float) $delivery->delivery_longitude,
                'address' => $delivery->delivery_adresse,
            ],
        ]);
    }
}
