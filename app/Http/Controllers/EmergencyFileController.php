<?php

namespace App\Http\Controllers;

use App\Models\EmergencyFile;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmergencyFileController extends Controller
{
    /**
     * @throws Exception
     */
    public function createEmergencyFile(Request $request, $emergencyId) : EmergencyFile
    {
        $validator = validator($request->all(), [
            'userFile' => 'required|file|max:2048',
        ]);

        if ($validator->fails()) throw new Exception($validator->errors()->first());

        $path = $request->file('userFile')->store('files/emergency_' . $emergencyId, 's3');
        if($path === false) throw new Exception('Error uploading file');

        return EmergencyFile::create([
            'emergency_id' => $emergencyId,
            'name' => basename($path),
            'type' => $request->file('userFile')->extension(),
            'url' => Storage::disk('s3')->url($path),
        ]);
    }
}
