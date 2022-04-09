<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{

    public function getAgents()
    {
        $agents = Agent::all();
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
            return res('Authority not found', 404);
        }

        DB::beginTransaction();
        try {
            $user = User::find($agent->user_id);
            $agent->delete();
            $user->delete();

            DB::commit();
            return res('Agent deleted successfully');
        } catch (Exception $e) {
            return res($e->getMessage(), 400);
        }
    }
}
