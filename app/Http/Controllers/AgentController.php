<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{

    public function getAgents(Request $request)
    {
        $agents = Agent::query()->paginate($request->input('perPage') ?? 15);
        return res($agents);
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
        return res($agent);
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
            ->leftJoinSub($occupiedAgents, 'occupied_agents', function ($join) {
                $join->on('agents.id', '=', 'occupied_agents.agent_id');
            })
            ->where('occupied_agents.agent_id', null)
            ->paginate($request->input('per_page', 15));

        return res($availableAgents);
    }
}
