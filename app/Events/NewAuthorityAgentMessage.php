<?php

namespace App\Events;

use App\Models\AuthorityAgentChatMessage;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NewAuthorityAgentMessage
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatMessage;
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, AuthorityAgentChatMessage $chatMessage)
    {
        $this->user = $user;
        $this->chatMessage = $chatMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn(): array|Channel
    {
        Log::info('HERE ' . json_encode($this->chatMessage));
        return [new Channel('private-agent-chat.' . $this->chatMessage->authority_agent_chat_room_id)];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message';
    }
}
