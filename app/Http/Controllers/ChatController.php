<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
{
    $user = Auth::user();
    $chats = $this->chatService->getUserChats($user);

    // احصل على المستخدم المدير
    $manager = User::where('role', 'manager')->first();

    return view('chat.index', compact('chats', 'manager'));
}

    public function getMessages(User $receiver)
    {
        $sender = Auth::user();

        $messages = Message::where(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $sender->id)
                  ->where('receiver_id', $receiver->id);
        })->orWhere(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $receiver->id)
                  ->where('receiver_id', $sender->id);
        })->orderBy('created_at', 'asc')
          ->with(['sender', 'receiver'])
          ->get();

        $this->chatService->markMessagesAsSeen($receiver, $sender);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'is_seen' => false
        ]);

        return response()->json($message->load(['sender', 'receiver']));
    }
}
