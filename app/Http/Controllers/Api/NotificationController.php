<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request) {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return NotificationResource::collection($notifications);
    }

    public function markRead(Notification $notification) {
        if ($notification->user_id !== request()->user()->id) {
            abort(403);
        }
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return new NotificationResource($notification);
    }

    public function markAllRead(Request $request) {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['message' => 'Toutes les notifications marquées comme lues.']);
    }
}
