<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use App\Services\Chat\ChatService;

class MessageController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function conversations(Request $request) {
        $userId = $request->user()->id;
        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver', 'article'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($message) use ($userId) {
                $otherId = $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
                return $otherId . '-' . ($message->article_id ?? '0');
            });

        $conversations = $messages->map(function($msgs) {
            return new MessageResource($msgs->first());
        })->values();

        return response()->json(['data' => $conversations]);
    }

    public function index(Request $request, User $user) {
        $messages = $this->chatService->getConversation($request->user(), $user, $request->article_id);
        return MessageResource::collection($messages);
    }

    public function store(Request $request, User $user) {
        // Note: the controller parameter must be named $user (not $receiver) to
        // match the {user} route segment, otherwise Laravel's implicit route
        // model binding silently fails and an empty User instance is injected
        // instead — resulting in a NULL receiver_id at the database level.
        $request->validate(['message' => 'required|string|max:5000']);
        $message = $this->chatService->sendMessage(
            $request->user(),
            $user,
            $request->message,
            $request->article_id
        );
        $message->load(['sender', 'receiver']);
        return new MessageResource($message, 201);
    }

    public function markRead(Message $message) {
        $this->authorize('view', $message);
        if ($message->receiver_id !== request()->user()->id) {
            abort(403);
        }
        $this->chatService->markAsRead($message);
        return new MessageResource($message);
    }
}
