<?php

namespace App\Http\Controllers;

use App\Models\EmergencyType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmergencyTypeController extends Controller
{
    public function getEmergencyTypes(): JsonResponse
    {
        $emergencyTypes = EmergencyType::all();
        return res($emergencyTypes);
    }

    public function getEmergencyType($id): JsonResponse
    {
        $emergencyType = EmergencyType::query()->find($id);
        if ($emergencyType) {
            return res($emergencyType);
        }

        return res('Emergency type not found', 404);
    }

    public function createEmergencyType(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $emergencyType = EmergencyType::query()->where('name', $request->name)->first();
        if ($emergencyType) {
            return res('Emergency type with the same name already exists', 400);
        }

        $emergencyType = EmergencyType::query()->create($request->toArray());
        return res($emergencyType);
    }

    public function deleteEmergencyType($id): JsonResponse
    {
        $emergencyType = EmergencyType::query()->find($id);
        if ($emergencyType) {
            $emergencyType->delete();
            return res('Emergency type deleted successfully');
        }
        return res('Emergency type not found', 404);
    }

    public function updateEmergencyType(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $emergencyType = EmergencyType::query()->where('name', $request->name)->first();
        if ($emergencyType) {
            return res('Emergency type with the same name already exists', 400);
        }

        $emergencyType = EmergencyType::query()->find($id);
        if ($emergencyType) {
            $emergencyType->update($request->all());
            return res('Emergency type updated successfully', 400);
        }
        return res('Emergency type not found', 404);
    }
}
