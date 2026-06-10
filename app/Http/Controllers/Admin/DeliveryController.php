<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Http\Resources\DeliveryResource;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request) {
        $query = Delivery::with(['order.article', 'rider']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('rider_id')) {
            $query->where('rider_id', $request->rider_id);
        }
        $deliveries = $query->orderBy('created_at', 'desc')->paginate(15);
        return DeliveryResource::collection($deliveries);
    }

    public function show(Delivery $delivery) {
        $delivery->load(['order.article', 'rider']);
        return new DeliveryResource($delivery);
    }

    public function update(Request $request, Delivery $delivery) {
        $request->validate(['status' => 'required|string']);
        $delivery->update(['status' => $request->status]);
        return new DeliveryResource($delivery);
    }
}
