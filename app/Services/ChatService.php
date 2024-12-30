<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

class ChatService
{
    public function getUserChats(User $user): Collection
    {
        $messages = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->groupMessagesByUser($messages, $user);
    }

    private function groupMessagesByUser(Collection $messages, User $currentUser): Collection
    {
        $chatUsers = collect();

        foreach ($messages as $message) {
            $otherUser = $message->sender_id === $currentUser->id
                ? $message->receiver
                : $message->sender;

            if (!$chatUsers->has($otherUser->id)) {
                $lastMessage = $messages->where(function ($query) use ($currentUser, $otherUser) {
                    return ($query->sender_id === $currentUser->id && $query->receiver_id === $otherUser->id) ||
                           ($query->sender_id === $otherUser->id && $query->receiver_id === $currentUser->id);
                })->first();

                $chatUsers->put($otherUser->id, [
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $this->getUnreadCount($messages, $currentUser, $otherUser)
                ]);
            }
        }

        return $chatUsers->sortByDesc(function ($chat) {
            return $chat['last_message']->created_at;
        })->values();
    }

    private function getUnreadCount(Collection $messages, User $currentUser, User $otherUser): int
    {
        return $messages->where('sender_id', $otherUser->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_seen', false)
            ->count();
    }

    public function markMessagesAsSeen(User $sender, User $receiver): void
    {
        Message::where('sender_id', $sender->id)
            ->where('receiver_id', $receiver->id)
            ->where('is_seen', false)
            ->update(['is_seen' => true]);
    }
}
