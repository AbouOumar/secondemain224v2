<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Http\Resources\DeliveryResource;
use Illuminate\Http\Request;
use App\Services\Delivery\TrackingService;
use App\Events\DeliveryAccepted;
use App\Events\DeliveryCompleted;

class DeliveryController extends Controller
{
    public function __construct(private TrackingService $trackingService) {}

    public function available(Request $request) {
        $deliveries = Delivery::where('status', 'en_attente')
            ->with(['order.article', 'rider'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return DeliveryResource::collection($deliveries);
    }

    public function accept(Delivery $delivery) {
        $this->authorize('accept', $delivery);
        $delivery->update([
            'status' => 'acceptee',
            'accepted_at' => now(),
        ]);
        event(new DeliveryAccepted($delivery));
        return new DeliveryResource($delivery);
    }

    public function pickup(Delivery $delivery) {
        $delivery->update([
            'status' => 'en_cours',
            'picked_up_at' => now(),
        ]);
        return new DeliveryResource($delivery);
    }

    public function complete(Delivery $delivery) {
        $this->authorize('complete', $delivery);
        $delivery->update([
            'status' => 'effectuee',
            'completed_at' => now(),
        ]);
        event(new DeliveryCompleted($delivery));
        return new DeliveryResource($delivery);
    }

    public function tracking(Request $request, Delivery $delivery) {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $this->trackingService->updatePosition($delivery, $request->latitude, $request->longitude);
        return response()->json(['message' => 'Position mise à jour.']);
    }

    public function setStatus(Request $request) {
        $request->validate(['status' => 'required|string|in:en_ligne,occupe,hors_ligne']);
        $request->user()->update(['rider_status' => $request->status]);
        return response()->json(['message' => 'Statut mis à jour.']);
    }

    public function history(Request $request) {
        $deliveries = Delivery::where('rider_id', $request->user()->id)
            ->with(['order.article'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return DeliveryResource::collection($deliveries);
    }
}
