<?php

namespace App\Http\Controllers;

use App\Events\NewChatMessage;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatRoomController extends Controller
{
    public function getAllChatRooms(Request $request): JsonResponse
    {
        $chatRooms = ChatRoom::query()->paginate($request->input('perPage') ?? 15);
        return res($chatRooms);
    }

    public function getChatRoom(Request $request, $id): JsonResponse
    {
        $chatRoom = ChatRoom::find($id);
        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $payload = $chatRoom->toArray();
        $payload['messages'] = $chatRoom->getMessages();
        return res($payload);
    }

    public function postMessageToChatRoom(Request $request, $id): JsonResponse
    {
        $user = User::find(Auth::user()->id);
        $chatRoom = ChatRoom::find($id);
        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $message = ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        event(new NewChatMessage($user, $message));

        return res($message);
    }

    public function getChatRoomMessages(Request $request, $id): JsonResponse
    {
        $chatRoom = ChatRoom::find($id);
        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $messages = ChatMessage::query()
            ->select(
                'chat_messages.*',
                'users.name as user_name',
            )
            ->join('users', 'users.id', '=', 'chat_messages.user_id')
            ->where('chat_room_id', $chatRoom->id)
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 25);

        return res($messages);
    }
}
