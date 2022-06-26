<?php

namespace App\Services;

use App\Enum\UserTypeEnum;
use App\Models\ChatRoom;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatRoomService
{
    public function checkIfAuthorized(User $user, ChatRoom $chatRoom): bool
    {
        $emergency = Emergency::query()->find($chatRoom->emergency_id);
        if(compareWithEnum($user->type, UserTypeEnum::USER)) {
            Log::info('USER ID ' . $user->id . ' reporting_user_id ' . $emergency->reporting_user_id);
            if($user->id != $emergency->reporting_user_id) return false;
        }
        else {
            $agent = $user->getAgent();
            $emergencyAgent = EmergencyAgent::query()->where('emergency_id', $emergency->id)
                ->where('agent_id', $agent->id)->first();

            if(!$emergencyAgent) return false;
        }

        return true;
    }
}
