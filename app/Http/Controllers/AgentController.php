<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgentCollection;
use App\Http\Resources\AgentResource;
use App\Models\Agent;
use App\Models\AgentType;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\User;
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
        return new AgentCollection(kaantable(Agent::query(), $request));
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
        try {
         $type = AgentType::query()->find($request->agent_type_id);
         if(!$type)
         {
             return res('Agent type not found', 404);
         }

         $payload = $request->all();
         $payload['agent_type_id'] = $type->id;

         Agent::query()->create($payload);
         return res('Agent created');
        } catch (Exception $e) {
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

        return res($chat_rooms);
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
}
