<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Events\NewChatMessage;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
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
        $emergency = Emergency::query()->find($chatRoom->emergency_id);

        if(!$chatRoom) return res('Chat room not found', 404);

        if($user->type == UserTypeEnum::USER) {
            $customer = $user->getCustomer();
            if(!$customer || $customer->id != $emergency->reporting_customer_id)
                return res('Unauthorized', 401);
        }
        else {
            $agent = $user->getAgent();
            $emergencyAgent = EmergencyAgent::query()->where('emergency_id', $emergency->id)
                ->where('agent_id', $agent->id)->first();

            if(!$emergencyAgent)
                return res('Unauthorized', 401);
        }

        $message = ChatMessage::create([
            'chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        broadcast(new NewChatMessage($user, $message))->toOthers();

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
