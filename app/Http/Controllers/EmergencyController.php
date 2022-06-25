<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmergencyCollection;
use App\Http\Resources\Forms\EmergencyResource;
use App\Models\Agent;
use App\Models\Authority;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\EmergencyFile;
use App\Models\EmergencyType;
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
        return new EmergencyCollection(kaantable(Emergency::query(), $request));
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

        $customer = Customer::query()->where('user_id', $user->id)->first();
        if(!$customer) {
            return res('Customer not found', 404);
        }
        if(!$customer->verified) return res('Customer not verified', 400);

        try {
            DB::beginTransaction();
            // Create the emergency
            $payload = array_merge($request->all(), [
                'reporting_customer_id' => $customer->id,
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

                EmergencyAgent::query()->create([
                    'emergency_id' => $emergency->id,
                    'agent_id' => $agent->id,
                ]);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            return res($exception->getMessage(), 500);
        }


        return res('Agent(s) assigned to emergency');
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

        $fileData = Storage::disk('s3')->get($file);

        $headers = [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];

        return response()->stream(function () use ($fileData) {
            echo $fileData;
        }, 200, $headers);
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

            $descriptions = [];
            $files = [];
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
                'reporting_customer_id' => $mainEmergency->reporting_customer_id,
                'emergency_type_id' => $mainEmergency->emergency_type_id,
            ]);

            foreach ($files as $file) {
                Log::info('FILE: ' . json_encode($file));
                EmergencyFile::query()->create([
                    'emergency_id' => $emergency->id,
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'url' => $file['url'],
                ]);
            }

            DB::commit();
            return res('Emergencies merged');
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

        $customer = Customer::query()->where('user_id', $user->id)->first();

        $emergencies = Emergency::query()
            ->select(
                'emergencies.*',
                'chat_rooms.id as chat_room_id'
            )
            ->join('chat_rooms', 'emergencies.id', '=', 'chat_rooms.emergency_id')
            ->where('reporting_customer_id', $customer->id)
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
        $emergencyAgents = EmergencyAgent::query()->where('emergency_id', $id)->get();
        $emergencyFiles = EmergencyFile::query()->where('emergency_id', $id)->get();
        $reportingCustomer = Customer::query()->find($emergency->reporting_customer_id);
        $approvingAuthority = Authority::query()->find($emergency->approving_authority_id);
        $chatRoom = ChatRoom::query()->where('emergency_id', $id)->first();

        return res ([
            'emergency' => $emergency->toArray(),
            'assigned_agents' => $emergencyAgents->toArray(),
            'emergency_files' => $emergencyFiles->toArray(),
            'chat_room' => $chatRoom?->toArray(),
            'emergency_types' => $emergencyTypes->toArray(),
            'reporting_customer' => $reportingCustomer->toArray(),
            'approving_authority' => $approvingAuthority?->toArray(),
        ]);
    }
}
