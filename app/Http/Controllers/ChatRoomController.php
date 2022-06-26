<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Events\NewChatMessage;
use App\Events\NewNotification;
use App\Models\Agent;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\User;
use App\Services\ChatRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        if(!$chatRoom) return res('Chat room not found', 404);

        if(!$this->checkIfAuthorized($user, $chatRoom)) {
            return res('Unauthorized', 401);
        }

        $message = ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        broadcast(new NewChatMessage($user, $message))->toOthers();

        $notifyUser = $this->getChatRoomRespondentId($chatRoom, $user);
        event(new NewNotification($notifyUser, ['payload' => ['message' => $request->message, 'user' => $user], 'type' => 'chatroom-message', 'title' => 'New Chatroom Message' ]));

        return res($message);
    }

    public function getChatRoomMessages(Request $request, $id): JsonResponse
    {
        $user = User::find(Auth::user()->id);
        $chatRoom = ChatRoom::find($id);

        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $chatRoomService = new ChatRoomService();
        if(!$chatRoomService->checkIfAuthorized($user, $chatRoom)) {
            return res('Unauthorized', 401);
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

    private function getChatRoomRespondentId(ChatRoom $chatRoom, User $user) : User {
        $emergency = $chatRoom->getEmergency();
        if($user->type == 'user') {
            return User::find(Agent::find(EmergencyAgent::query()->where('emergency_id', $emergency->id)->first()->agent_id)->user_id);
        } else {
            return User::find($emergency->reporting_user_id);
        }
    }
}
