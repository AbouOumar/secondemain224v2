<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Http\Resources\DeliveryResource;
use Illuminate\Http\Request;
use App\Services\Delivery\TrackingService;
use App\Services\Escrow\EscrowService;
use App\Events\DeliveryAccepted;
use App\Events\DeliveryDelivered;
use App\Events\DeliveryCompleted;

class DeliveryController extends Controller
{
    public function __construct(
        private TrackingService $trackingService,
        private EscrowService $escrow,
    ) {}

    public function available(Request $request) {
        abort_unless($request->user()->role?->value === 'motard', 403);

        $deliveries = Delivery::where('status', 'en_attente')
            ->whereNull('rider_id')
            ->with(['order.article'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return DeliveryResource::collection($deliveries);
    }

    /** Courses en cours du livreur (acceptées, en route, ou livrées en attente de confirmation du client). */
    public function current(Request $request) {
        abort_unless($request->user()->role?->value === 'motard', 403);

        $deliveries = Delivery::where('rider_id', $request->user()->id)
            ->whereIn('status', ['acceptee', 'en_cours', 'livree'])
            ->with(['order.article', 'order.buyer', 'order.seller'])
            ->orderBy('updated_at', 'desc')
            ->get();
        return DeliveryResource::collection($deliveries);
    }

    public function earnings(Request $request) {
        abort_unless($request->user()->role?->value === 'motard', 403);

        $now = now();
        $base = Delivery::where('rider_id', $request->user()->id)->where('status', 'effectuee');

        return response()->json(['data' => [
            'aujourd_hui' => (int) (clone $base)->whereDate('completed_at', $now->toDateString())->sum('prix'),
            'cette_semaine' => (int) (clone $base)->whereBetween('completed_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->sum('prix'),
            'ce_mois' => (int) (clone $base)->whereMonth('completed_at', $now->month)->whereYear('completed_at', $now->year)->sum('prix'),
            'cette_annee' => (int) (clone $base)->whereYear('completed_at', $now->year)->sum('prix'),
        ]]);
    }

    public function accept(Delivery $delivery) {
        $this->authorize('accept', $delivery);

        // Prise atomique : si un autre livreur a pris la course entre-temps, aucune ligne n'est modifiée.
        $riderId = request()->user()->id;
        $claimed = Delivery::whereKey($delivery->id)
            ->whereIn('status', ['en_attente', 'assignee'])
            ->where(fn ($q) => $q->whereNull('rider_id')->orWhere('rider_id', $riderId))
            ->update(['rider_id' => $riderId, 'status' => 'acceptee', 'accepted_at' => now()]);

        if (! $claimed) {
            return response()->json(['message' => 'Cette livraison a déjà été prise par un autre livreur.'], 409);
        }

        $delivery->refresh();
        event(new DeliveryAccepted($delivery));
        return new DeliveryResource($delivery);
    }

    public function pickup(Delivery $delivery) {
        $this->authorize('pickup', $delivery);
        $delivery->update([
            'status' => 'en_cours',
            'picked_up_at' => now(),
        ]);
        return new DeliveryResource($delivery);
    }

    public function complete(Delivery $delivery) {
        $this->authorize('complete', $delivery);
        $delivery->update([
            'status' => 'livree',
            'delivered_at' => now(),
        ]);
        event(new DeliveryDelivered($delivery));
        return new DeliveryResource($delivery);
    }

    public function confirmReceipt(Request $request, Delivery $delivery) {
        $user = $request->user();
        $isBuyer = $delivery->order && $delivery->order->buyer_id === $user->id;
        $isAdmin = $user->role?->value === 'admin';

        if (!($isBuyer || $isAdmin)) {
            // Le livreur ne peut pas confirmer lui-même la réception : c'est
            // ce qui débloque les fonds au vendeur, donc seul le client (ou
            // un admin en cas de litige) en a le droit.
            return response()->json(['message' => 'Seul le client peut confirmer la réception de sa livraison.'], 403);
        }

        if ($delivery->status->value !== 'livree') {
            return response()->json(['message' => 'Cette livraison ne peut pas encore être confirmée : elle n\'a pas été marquée comme livrée.'], 422);
        }

        $delivery->update([
            'status' => 'effectuee',
            'completed_at' => now(),
        ]);
        if ($delivery->order && $delivery->order->status->value !== 'livre') {
            $delivery->order->update(['status' => 'livre']);
        }
        if ($delivery->order && $delivery->order->escrow) {
            $this->escrow->release($delivery->order->escrow, 'confirmation_reception');
        }
        event(new DeliveryCompleted($delivery));
        return new DeliveryResource($delivery);
    }

    public function tracking(Request $request, Delivery $delivery) {
        $this->authorize('track', $delivery);
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
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
