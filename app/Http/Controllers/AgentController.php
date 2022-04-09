<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Exception;

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

    // update agent
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
}
