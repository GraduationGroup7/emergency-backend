<?php

namespace App\Http\Controllers;

use App\Events\NewAuthorityAgentMessage;
use App\Events\NewChatMessage;
use App\Events\NewNotification;
use App\Http\Resources\AuthorityCollection;
use App\Http\Resources\Forms\AuthorityResource;
use App\Models\Agent;
use App\Models\Authority;
use App\Models\AuthorityAgentChatMessage;
use App\Models\AuthorityAgentChatRoom;
use App\Models\AuthorityType;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthorityController extends Controller
{
    public function getAuthorities(Request $request): AuthorityCollection
    {
        $authorities = Authority::query()
            ->select('authorities.*', 'authority_types.name as type')
            ->join('authority_types', 'authority_types.id', '=', 'authorities.authority_type_id');

        return new AuthorityCollection(kaantable($authorities, $request));
    }

    public function getAuthority(int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        return res($authority);
    }

    public function updateAuthority(Request $request, int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        $validator = validator($request->all(), [
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'user_id' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $authority->update($request->all());
        User::find($authority->user_id)->update([
            'phone_number' => $request->phone_number,
        ]);

        return res($authority);
    }

    /**
     * @throws Exception
     */
    public static function createAuthority($data)
    {
        $validator = validator($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        return Authority::query()->create($data);
    }

    public function deleteAuthority(int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        DB::beginTransaction();
        try {
            $user = User::find($authority->user_id);
            $authority->delete();
            $user->delete();

            DB::commit();
            return res('Authority deleted successfully');
        } catch (Exception $e) {
            return res('Authority could not be deleted', 400);
        }
    }

    public function getAuthorityForm(Request $request, $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        return res(new AuthorityResource($authority));
    }

    public function getAuthorityCreateForm(Request $request): JsonResponse
    {
        $newAuthority = new Authority();
        return res(new AuthorityResource($newAuthority));
    }


    public function deleteAuthorityById($id) {
        $authority = Authority::find($id);
        if(!$authority) throw new Exception('Authority not found');

        $user = User::find($authority->user_id);
        $authority->delete();
        $user->delete();
    }

    public function bulkDeleteAuthorities(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach($request->ids as $authorityId) {
                $this->deleteAuthorityById($authorityId);
            }
            DB::commit();
            return res('Authorities deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return res('Authorities could not be deleted', 400);
        }
    }

    public function createAuthorityFromForm(Request $request) {
        $validator = validator($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $type = AuthorityType::query()->find($request->type)->first();
            if(!$type)
            {
                return res('Authority type not found', 404);
            }

            $payload = $request->all();
            $payload['authority_type_id'] = $type->id;

            $user = User::create([
                'email' => $request->email,
                'name' => $request->first_name . ' ' . $request->last_name,
                'password' => bcrypt($request->password),
                'phone_number' => $request->phone_number,
                'type' => 'authority'
            ]);
            $payload['user_id'] = $user->id;

            Authority::query()->create($payload);

            DB::commit();
            return res('Authority created successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            return res('Authority could not be created', 400);
        }
    }

    public function getAuthorityChatRooms(Request $request): JsonResponse
    {
        $user = Auth::user();
        $authority = Authority::query()->where('user_id', $user->id)->first();
        if(!$authority) return res('Authority not found', 404);

        $chatRooms = AuthorityAgentChatRoom::query()
            ->select(
                'authority_agent_chat_rooms.*',
                DB::raw("CONCAT(agents.first_name, ' ', agents.last_name) as agent_name")
            )
            ->join('agents', 'agent_user_id', '=', 'agents.user_id')
            ->where('authority_user_id', $user->id)
            ->get()->toArray();

        return res($chatRooms);
    }

    public function sendMessage(Request $request, $id) {
        $user = User::find(Auth::user()->id);
//        $authority = Authority::query()->where('user_id', $user->id)->first();
//        if(!$authority) return res('Authority not found', 404);

        $chatRoom = AuthorityAgentChatRoom::query()->find($id);

        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $message = AuthorityAgentChatMessage::create([
            'authority_agent_chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        broadcast(new NewAuthorityAgentMessage($user, $message))->toOthers();

        return res('Message sent successfully');
    }

    public function getChatMessages(Request $request, $id): JsonResponse
    {
        $chatRoom = AuthorityAgentChatRoom::find($id);
        if(!$chatRoom) return res('Chat room not found', 404);

        $messages = AuthorityAgentChatMessage::query()
            ->select('authority_agent_chat_messages.*', 'users.name as user_name')
            ->join('users', 'authority_agent_chat_messages.user_id', '=', 'users.id')
            ->where('authority_agent_chat_room_id', $id)
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 25);

        return res($messages);
    }

    public function openChatRoom(Request $request): JsonResponse
    {
        $user = User::find(Auth::user()->id);
        $agentUser = Agent::query()->find($request->agent_id);

        $chatRoom = AuthorityAgentChatRoom::query()->updateOrCreate([
            'authority_user_id' => $user->id,
            'agent_user_id' => $agentUser->user_id,
        ]);

        return res($chatRoom);
    }
}
