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
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($message) use ($userId) {
                return $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
            });

        $conversations = $messages->map(function($msgs) {
            return new MessageResource($msgs->first());
        })->values();

        return response()->json(['data' => $conversations]);
    }

    public function index(Request $request, User $user) {
        $messages = $this->chatService->getConversation($request->user(), $user);
        return MessageResource::collection($messages);
    }

    public function store(Request $request, User $receiver) {
        $request->validate(['message' => 'required|string|max:5000']);
        $message = $this->chatService->sendMessage(
            $request->user(),
            $receiver,
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
