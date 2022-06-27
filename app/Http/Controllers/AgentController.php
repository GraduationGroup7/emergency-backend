<?php

namespace App\Http\Controllers;

use App\Events\NewAuthorityAgentMessage;
use App\Events\NewChatMessage;
use App\Events\NewNotification;
use App\Http\Resources\AgentCollection;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\AgentType;
use App\Models\AuthorityAgentChatMessage;
use App\Models\AuthorityAgentChatRoom;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\User;
use App\Services\ChatRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{

    public function getAgents(Request $request)
    {
        $agents = Agent::query()
            ->select('agents.*', 'agent_types.name as type')
            ->join('agent_types', 'agent_types.id', '=', 'agents.agent_type_id');

        return new AgentCollection(kaantable($agents, $request));
    }

    public function getAgent($id)
    {
        $agent = Agent::find($id);
        if(!$agent)
        {
            return res('Agent not found', 404);
        }
        return res($agent);
    }

    public function updateAgent(Request $request, $id)
    {
        $agent = Agent::find($id);
        if(!$agent)
        {
            return res('Agent not found', 404);
        }
        $agent->update($request->all());
        User::find($agent->user_id)->update([
            'phone_number' => $request->phone_number,
        ]);

        return res($agent);
    }

    public function createAgentRoute(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
             $type = AgentType::query()->find($request->type)->first();
             if(!$type)
             {
                 return res('Agent type not found', 404);
             }

             $payload = $request->all();
             $payload['agent_type_id'] = $type->id;
             $user = User::create([
                    'email' => $request->email,
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'password' => bcrypt($request->password),
                    'phone_number' => $request->phone_number,
                    'type' => 'agent'
             ]);
            $payload['user_id'] = $user->id;

            Agent::query()->create($payload);
            DB::commit();

            return res('Agent created');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return res('Error creating agent route', 500);
        }
    }

    /**
     * @throws Exception
     */
    public static function createAgent($data)
    {
        $validator = validator($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        return Agent::query()->create($data);
    }

    public function deleteAgent($id)
    {
        $agent = Agent::query()->find($id);
        if (!$agent) {
            return res('Agent not found', 404);
        }

        DB::beginTransaction();
        try {
            $user = User::find($agent->user_id);
            $agent->delete();
            $user->delete();

            DB::commit();
            return res('Agent deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return res('Agent could not be deleted', 400);
        }
    }

    public function getAvailableAgents(Request $request): JsonResponse
    {
        $occupiedAgents = Emergency::query()
            ->select('ea.agent_id as agent_id')
            ->join('emergency_agents as ea', 'ea.emergency_id', '=', 'emergencies.id')
            ->where('completed', false)
            ->where('is_active', true);

        $availableAgents = Agent::query()
            ->select(
                'agents.*',
                'agent_types.name as agent_type_name',
                'users.phone_number as phone_number'
            )
            ->leftJoinSub($occupiedAgents, 'occupied_agents', function ($join) {
                $join->on('agents.id', '=', 'occupied_agents.agent_id');
            })
            ->join('agent_types', 'agents.agent_type_id', '=', 'agent_types.id')
            ->join('users', 'agents.user_id', '=', 'users.id')
            ->where('occupied_agents.agent_id', null)
            ->paginate($request->input('per_page', 15));

        return res($availableAgents);
    }

    public function getAgentForm(Request $request, $id): JsonResponse
    {
        $agent = Agent::find($id);
        if(!$agent) {
            return res('Agent not found', 404);
        }

        return res(new \App\Http\Resources\Forms\AgentResource($agent));
    }

    public function getAgentCreateForm(Request $request): JsonResponse
    {
        $agent = new Agent();
        return res(new \App\Http\Resources\Forms\AgentResource($agent));
    }

    public function getAgentChatRooms(Request $request): JsonResponse
    {
        $user = Auth::user();
        if($user->type != 'agent') return res('Unauthorized', 401);

        $agent = Agent::where('user_id', $user->id)->first();
        if(!$agent) {
            return res('Agent not found', 404);
        }

        $chat_rooms = EmergencyAgent::query()
            ->select('chat_rooms.id as chat_room_id', 'emergencies.*')
            ->join('emergencies', 'emergencies.id', '=', 'emergency_agents.emergency_id')
            ->join('chat_rooms', 'chat_rooms.emergency_id', '=', 'emergencies.id')
            ->where('emergency_agents.agent_id', $agent->id)
            ->where('emergencies.completed', false)
            ->get();

        $payload = $chat_rooms->toArray();
        $payload = array_merge($payload, AuthorityAgentChatRoom::query()
            ->select('authority_agent_chat_rooms.*', 'authority_agent_chat_rooms.id as chat_room_id',
            DB::raw("'agent_chat' as chat_room_type"))
            ->where('agent_user_id', $user->id)->get()->toArray());

        return res($payload);
    }

    public function deleteAgentById($id) {
        $agent = Agent::find($id);
        if(!$agent) throw new Exception('Agent not found');

        $user = User::find($agent->user_id);
        $agent->delete();
        $user->delete();
    }

    public function bulkDeleteAgents(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            foreach($request->ids as $agentId) {
                $this->deleteAgentById($agentId);
            }
            DB::commit();
            return res('Agents deleted successfully');
        } catch (Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return res('Agents could not be deleted', 400);
        }
    }

    public function getAvailableAgentsCollection(Request $request) {
        $occupiedAgents = Emergency::query()
            ->select('ea.agent_id as agent_id')
            ->join('emergency_agents as ea', 'ea.emergency_id', '=', 'emergencies.id')
            ->where('completed', false);

        $availableAgents = Agent::query()
            ->select(
                'agents.*',
                'agent_types.name as agent_type_name',
                'users.phone_number as phone_number'
            )
            ->leftJoinSub($occupiedAgents, 'occupied_agents', function ($join) {
                $join->on('agents.id', '=', 'occupied_agents.agent_id');
            })
            ->join('agent_types', 'agents.agent_type_id', '=', 'agent_types.id')
            ->join('users', 'agents.user_id', '=', 'users.id')
            ->where('occupied_agents.agent_id', null);

        return new AgentCollection(kaantable($availableAgents, $request));
    }

    public function sendMessageToAuthority(Request $request, $id): JsonResponse
    {
        $user = User::find(Auth::user()->id);
        $chatRoom = AuthorityAgentChatRoom::find($id);

        if(!$chatRoom) return res('Chat room not found', 404);

        $message = AuthorityAgentChatMessage::create([
            'authority_agent_chat_room_id' => $chatRoom->id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        broadcast(new NewAuthorityAgentMessage($user, $message))->toOthers();

        return res('Message sent successfully');
    }
}
