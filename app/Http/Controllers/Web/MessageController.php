<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Article;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function index()
    {
        $userId = Auth::id();
        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver', 'article'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                $otherId = $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
                return $otherId . '-' . ($message->article_id ?? '0');
            });

        $conversations = $messages->map(function ($msgs) {
            return $msgs->first();
        })->values();

        $unreadCount = Message::where('receiver_id', $userId)->where('is_read', false)->count();

        return view('messages.index', compact('conversations', 'unreadCount'));
    }

    public function show(User $user, ?Article $article = null)
    {
        $query = Message::where(function ($q) use ($user) {
                $q->where('sender_id', Auth::id())->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($user) {
                $q->where('sender_id', $user->id)->where('receiver_id', Auth::id());
            });

        if ($article) {
            $query->where('article_id', $article->id);
        }

        $conversation = $query->orderBy('created_at', 'asc')->get();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->when($article, fn($q) => $q->where('article_id', $article->id))
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('messages.show', compact('conversation', 'user', 'article'));
    }

    public function store(Request $request, User $receiver)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'article_id' => 'nullable|exists:articles,id',
        ]);

        $this->chatService->sendMessage(
            Auth::user(),
            $receiver,
            $request->message,
            $request->article_id
        );

        $params = ['user' => $receiver->id];
        if ($request->article_id) {
            $params['article'] = $request->article_id;
        }

        return redirect()->route('messages.show', $params)->with('success', 'Message envoyé.');
    }
}
