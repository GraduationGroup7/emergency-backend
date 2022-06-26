<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorityAgentChatMessage extends Model
{
    protected $fillable = [
        'authority_agent_chat_room_id',
        'user_id',
        'message',
    ];

    use HasFactory;
}
