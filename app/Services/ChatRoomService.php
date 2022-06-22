<?php

namespace App\Services;

use App\Enum\UserTypeEnum;
use App\Models\ChatRoom;
use App\Models\Emergency;
use App\Models\EmergencyAgent;
use App\Models\User;

class ChatRoomService
{
    public function checkIfAuthorized(User $user, ChatRoom $chatRoom): bool
    {
        $emergency = Emergency::query()->find($chatRoom->emergency_id);
        if(compareWithEnum($user->type, UserTypeEnum::USER)) {
            $customer = $user->getCustomer();
            if(!$customer || $customer->id != $emergency->reporting_customer_id) return false;
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
