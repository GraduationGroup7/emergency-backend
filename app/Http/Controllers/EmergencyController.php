<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Events\NewNotification;
use App\Http\Resources\EmergencyCollection;
use App\Http\Resources\Forms\EmergencyResource;
use App\Models\Agent;
use App\Models\Authority;
use App\Models\AuthorityType;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\EmergencyFile;
use App\Models\EmergencyNote;
use App\Models\EmergencyType;
use App\Models\User;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmergencyController extends Controller
{
    public function getEmergencies(Request $request): EmergencyCollection
    {
        $user = User::find(Auth::user()->id);

        $emergencies = Emergency::query()
            ->select('emergencies.*', 'emergency_types.name as emergency_type')
            ->join('emergency_types', 'emergencies.emergency_type_id', '=', 'emergency_types.id')
            ->where(function ($query) use ($user) {
                if($user->type === 'authority') {
                    $authority = Authority::query()->where('user_id', $user->id)->first();
                    $authorityType = AuthorityType::find($authority->authority_type_id);
                    $query->where('emergency_types.name', $authorityType->name)
                        ->where('emergencies.is_active', true);
                }
            });

        return new EmergencyCollection(kaantable($emergencies, $request));
    }

    public function getEmergency(Request $request, int $id): JsonResponse
    {
        $emergency = Emergency::find($id);
        if(!$emergency) {
            return res('Emergency not found', 404);
        }

        $payload = $emergency->toArray();
        $payload['files'] = [];

        // fetch the emergency files
        $files = EmergencyFile::query()->where('emergency_id', $id)->get();
        foreach ($files as $file) {
            $payload['files'][] = '/emergencies/' . $id . '/get_file/' . $file->name;
        }

        return res($payload);
    }

    public function createEmergency(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = validator($request->all(), [
            'description' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        try {
            DB::beginTransaction();
            // Create the emergency
            $payload = array_merge($request->all(), [
                'reporting_user_id' => $user->id,
            ]);

            $emergency = Emergency::query()->create($payload);

            // Create the chat room for the emergency
            ChatRoom::query()->create([
                'emergency_id' => $emergency->id
            ]);

            foreach ($request->file() as $file) {
                $filePath = 'files/emergency_' . $emergency->id . '/';
                $path = Storage::disk('s3')->putFileAs($filePath, $file, $file->getClientOriginalName());
                if($path === false) throw new Exception('Error uploading file');

                Log::info('Uploaded file ' . $path);

                EmergencyFile::create([
                    'emergency_id' => $emergency->id,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->extension(),
                    'url' => Storage::disk('s3')->url($path),
                    's3_url' => 'emergencies/' . $emergency->id . '/get_file/' . $file->getClientOriginalName(),
                ]);
            }
            DB::commit();

            return res($emergency);
        } catch (Exception $exception) {
            DB::rollBack();
            return res($exception->getMessage(), 500);
        }
    }

    public function assignAgentsToEmergency(Request $request, int $id): JsonResponse
    {
        $validator = validator($request->all(), [
            'agent_ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $emergency = Emergency::find($id);
        if(!$emergency) {
            return res('Emergency not found', 404);
        }

        try {
            DB::beginTransaction();
            $agentIds = $request->input('agent_ids');
            foreach ($agentIds as $agentId) {
                $agent = Agent::find($agentId);
                if(!$agent) {
                    return res('Agent with id ' . $agent->id . ' could not be found', 404);
                }

                $emergencyAgent = EmergencyAgent::query()
                    ->where('emergency_id', $emergency->id)
                    ->where('agent_id', $agent->id)->first();

                if($emergencyAgent) continue;

                // Assign agent to emergency
                EmergencyAgent::query()->create([
                    'emergency_id' => $emergency->id,
                    'agent_id' => $agent->id,
                ]);
                // create the notification for the user
                $user = User::find($agent->user_id);
                event(new NewNotification($user, [
                    'payload' => [
                        'emergency_id' => $emergency->id,
                        'message' => 'You have been assigned to an emergency',
                    ],
                    'type' => 'assigned-to-emergency',
                    'title' => 'Assigned to Emergency',
                ]));
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return res($exception->getMessage(), 500);
        }

        $emergency = EmergencyAgent::query()
            ->select('agents.*', 'agent_types.name as type')
            ->join('agents', 'emergency_agents.agent_id', '=', 'agents.id')
            ->join('agent_types', 'agents.agent_type_id', '=', 'agent_types.id')
            ->where('emergency_id', $emergency->id,
        )->get()->toArray();
        return res($emergency);
    }

    public function removeAgentsFromEmergency(Request $request, int $id): JsonResponse
    {
        $validator = validator($request->all(), [
            'agent_ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $emergency = Emergency::find($id);
        if(!$emergency) {
            return res('Emergency not found', 404);
        }

        try {
            DB::beginTransaction();
            $agentIds = $request->input('agent_ids');
            foreach ($agentIds as $agentId) {
                $agent = Agent::find($agentId);
                if(!$agent) {
                    return res('Agent with id ' . $agent->id . ' could not be found', 404);
                }

                $emergencyAgent = EmergencyAgent::query()
                    ->where('emergency_id', $emergency->id)
                    ->where('agent_id', $agent->id)->first();

                if(!$emergencyAgent) continue;

                $emergencyAgent->delete();
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return res($exception->getMessage(), 500);
        }

        return res('Agent(s) removed from emergency');
    }

    public function getEmergencyFile(Request $request, $id, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Get the file from s3
        $file = 'files/emergency_' . $id . '/' . $filename;

        $s3Service = new S3Service();
        return $s3Service->getFile($file);
    }

    public function getChatRoom(Request $request, $id): JsonResponse
    {
        $emergency = Emergency::find($id);
        if(!$emergency) {
            return res('Emergency not found', 404);
        }

        $chatRoom = ChatRoom::query()->where('emergency_id', $id)->first();
        if(!$chatRoom) {
            return res('Chat room not found', 404);
        }

        $payload = $chatRoom->toArray();
        $payload['messages'] = $chatRoom->getMessages();
        return res($payload);
    }

    public function getArchivalEmergencies(Request $request): JsonResponse
    {
        $completedEmergencies = Emergency::query()
            ->where('completed', true)
            ->paginate($request->input('perPage', 15));

        return res($completedEmergencies);
    }

    public function mergeEmergencies(Request $request) {
        $mainEmergency = Emergency::find($request->mainEmergencyId);
        if (!$mainEmergency) {
            return res('Main emergency not found', 404);
        }

        $emergencyIds = $request->emergencyIds;

        try {
            DB::beginTransaction();

            $mainEmergency->is_active = false;
            $mainEmergency->save();

            $descriptions = [];
            $files = [];
            $files = array_merge($files, EmergencyFile::query()->where('emergency_id', $mainEmergency->id)->get()->toArray());

            foreach ($emergencyIds as $emergencyId) {
                $emergency = Emergency::find($emergencyId);
                if(!$emergency) {
                    return res('Emergency with id ' . $emergency->id . ' could not be found', 404);
                }

                $descriptions[] = $emergency->description;
                $files = array_merge($files, EmergencyFile::query()->where('emergency_id', $emergency->id)->get()->toArray());

                $emergency->is_active = false;
                $emergency->save();
            }

            // Create the emergency
            $emergency = Emergency::query()->create([
                'description' => implode('\n', $descriptions),
                'latitude' => $mainEmergency->latitude,
                'longitude' => $mainEmergency->longitude,
                'reporting_user_id' => $mainEmergency->reporting_user_id,
                'emergency_type_id' => $mainEmergency->emergency_type_id,
            ]);

            foreach ($files as $file) {
                Log::info('FILE: ' . json_encode($file));
                EmergencyFile::query()->create([
                    'emergency_id' => $emergency->id,
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'url' => $file['url'],
                    's3_url' => $file['s3_url'],
                ]);
            }

            DB::commit();
            return res($emergency);
        } catch (Exception $exception) {
            DB::rollBack();
            return res($exception->getMessage(), 500);
        }
    }

    public function getEmergenciesForUser(Request $request) {
        $user = $request->user();
        if (!$user || $user->type != 'user') {
            return res('User not found', 404);
        }

        $emergencies = Emergency::query()
            ->select(
                'emergencies.*',
                'chat_rooms.id as chat_room_id'
            )
            ->join('chat_rooms', 'emergencies.id', '=', 'chat_rooms.emergency_id')
            ->where('reporting_user_id', $user->id)
            ->where('completed', false)
            ->where('is_active', true)
            ->get();

        return res($emergencies);
    }

    public function updateEmergency(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'emergency_type_id' => 'required|exists:emergency_types,id',
            'description' => 'required|string',
            'latitude' => 'required',
            'longitude' => 'required',
            'status' => 'required|string',
            'completed' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        if($validator->fails()) return res($validator->errors(), 400);

        $emergency = Emergency::find($request->id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        $emergency->update([
            'emergency_type_id' => $request->emergency_type_id,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status,
            'completed' => $request->completed,
            'is_active' => $request->is_active,
        ]);

        return res('Emergency updated');
    }

    public function getEmergencyForm(Request $request, $id): JsonResponse
    {
        $emergency = Emergency::find($id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        return res(new EmergencyResource($emergency));
    }

    public function getEmergencyCreateForm(Request $request): JsonResponse
    {
        $emergency = new Emergency();
        return res(new EmergencyResource($emergency));
    }

    public function getAllEmergencyData(Request $request, $id): JsonResponse
    {
        $emergency = Emergency::query()->find($id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        $emergencyTypes = EmergencyType::all();
        $emergencyAgents = EmergencyAgent::query()
            ->select('agents.*', 'agent_types.name as type')
            ->join('agents', 'emergency_agents.agent_id', '=', 'agents.id')
            ->join('agent_types', 'agents.agent_type_id', '=', 'agent_types.id')
            ->where('emergency_id', $id)->get();
        $emergencyFiles = EmergencyFile::query()
            ->select('emergency_files.*',
                DB::raw("CONCAT('emergencies/', emergency_id, '/get_file/', name) as fileName")
            )
            ->where('emergency_id', $id)->get();
        $reportingUser = User::query()->find($emergency->reporting_user_id);
        $approvingAuthority = Authority::query()->find($emergency->approving_authority_id);
        $chatRoom = ChatRoom::query()->where('emergency_id', $id)->first();

        return res ([
            'emergency' => $emergency->toArray(),
            'assigned_agents' => $emergencyAgents->toArray(),
            'emergency_files' => $emergencyFiles->toArray(),
            'chat_room' => $chatRoom?->toArray(),
            'emergency_types' => $emergencyTypes->toArray(),
            'available_statuses' => [Emergency::STATUS_PENDING, Emergency::STATUS_ABANDONED, Emergency::STATUS_COMPLETED],
            'reporting_user' => $reportingUser->toArray(),
            'approving_authority' => $approvingAuthority?->toArray(),
        ]);
    }

    public function deleteEmergency(Request $request, $id) {
        $emergency = Emergency::query()->find($id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        DB::beginTransaction();
        try {
            // Delete the emergency agents
            EmergencyAgent::query()->where('emergency_id', $id)->delete();
            // Delete the chatroom messages and the chat room for the emergency chat room
            $chatRoom = ChatRoom::query()->where('emergency_id', $id)->first();
            if($chatRoom) {
                ChatMessage::query()->where('chat_room_id', $chatRoom->id)->delete();
                $chatRoom->delete();
            }

            // Delete the emergency files
            EmergencyFile::query()->where('emergency_id', $id)->delete();

            // Delete the emergency
            $emergency->delete();

            DB::commit();
            return res('Emergency deleted');
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            return res('Emergency could not be deleted', 500);
        }
    }

    private function deleteEmergencyById($id) {
        $emergency = Emergency::query()->find($id);
        if (!$emergency) throw new Exception('Emergency not found');

        // Delete the emergency agents
        EmergencyAgent::query()->where('emergency_id', $id)->delete();
        // Delete the chatroom messages and the chat room for the emergency chat room
        $chatRoom = ChatRoom::query()->where('emergency_id', $id)->first();
        if($chatRoom) {
            ChatMessage::query()->where('chat_room_id', $chatRoom->id)->delete();
            $chatRoom->delete();
        }

        // Delete the emergency files
        EmergencyFile::query()->where('emergency_id', $id)->delete();

        // Delete the emergency
        $emergency->delete();
    }

    public function bulkDeleteEmergencies(Request $request) {
        DB::beginTransaction();
        try {
            foreach ($request->ids as $emergencyId) {
                $this->deleteEmergencyById($emergencyId);
            }

            DB::commit();
            return res('emergencies deleted successfully');
        } catch (Exception $exception) {
            DB::rollBack();
            Log::info('Emergencies could not be deleted');
            return res('Emergencies could not be deleted', 500);
        }
    }

    public function getEmergencyTypes(Request $request): JsonResponse
    {
        $emergencyTypes = EmergencyType::query()->where('name', '!=', 'Test')->get()->toArray();
        return res($emergencyTypes);
    }

    public function getAgentEmergency(Request $request) {
        $user = Auth::user();
        if(!compareWithEnum($user->type, UserTypeEnum::AGENT)) {
            return res('User is not an emergency agent', 400);
        }

        $agent = Agent::query()->where('user_id', $user->id)->first();
        if(!$agent) {
            return res('Agent not found', 404);
        }

       $emergency = EmergencyAgent::query()
            ->select('emergencies.*', 'users.name as reported_by', 'users.phone_number', 'emergency_types.name as emergency_type')
            ->join('emergencies', 'emergency_agents.emergency_id', '=', 'emergencies.id')
            ->join('emergency_types', 'emergencies.emergency_type_id', '=', 'emergency_types.id')
            ->join('users', 'emergencies.reporting_user_id', '=', 'users.id')
            ->where('agent_id', $agent->id)
            ->where('emergencies.completed', false)
            ->where('emergencies.is_active', true)
            ->first();

        $payload = null;
        if($emergency) {
            $payload = $emergency->toArray();
            $payload['files'] = EmergencyFile::query()
                ->select(
                    'name',
                    DB::raw("CONCAT('emergencies/', emergency_id, '/get_file/', name) as url")
                )
                ->where('emergency_id', $payload['id'])->get()->toArray();

            $payload['notes'] = EmergencyNote::query()->where('emergency_id', $payload['id'])->get()->toArray();
        }

        return res($payload);
    }

    public function getEmergencyNotes(Request $request, $id): JsonResponse
    {
        $emergency = Emergency::query()->find($id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        $notes = EmergencyNote::query()->where('emergency_id', $id)->get()->toArray();
        return res($notes);
    }
    public function postEmergencyNote(Request $request, $id): JsonResponse
    {
        $emergency = Emergency::query()->find($id);
        if (!$emergency) {
            return res('Emergency not found', 404);
        }

        $notes = EmergencyNote::query()->create([
            'emergency_id' => $id,
            'user_id' => Auth::user()->id,
            'note' => $request->note,
        ]);

        return res($notes);
    }

    public function getMergeableEmergencies(Request $request, $id): EmergencyCollection
    {
        $user = User::find(Auth::user()->id);

        $emergencies = Emergency::query()
            ->select('emergencies.*', 'emergency_types.name as emergency_type')
            ->join('emergency_types', 'emergencies.emergency_type_id', '=', 'emergency_types.id')
            ->where('emergencies.id', '!=', $id)
            ->where('emergencies.is_active', true)
            ->where(function ($query) use ($user) {
                if($user->type === 'authority') {
                    $authority = Authority::query()->where('user_id', $user->id)->first();
                    $authorityType = AuthorityType::find($authority->authority_type_id);
                    $query->where('emergency_types.name', $authorityType->name);
                }
            });

        return new EmergencyCollection(kaantable($emergencies, $request));
    }
}
