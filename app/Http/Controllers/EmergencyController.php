<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\EmergencyFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmergencyController extends Controller
{
    public function getEmergencies(): JsonResponse
    {
        $emergencies = Emergency::all();
        return res($emergencies);
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

        try {
            DB::beginTransaction();
            // Create the emergency
            $payload = array_merge($request->all(), [
                'reporting_customer_id' => $customer->id,
            ]);

            $emergency = Emergency::query()->create($payload);

            Log::info('Files' . json_encode($request->file()));

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
}
