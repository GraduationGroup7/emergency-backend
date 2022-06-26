<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthorityAgentChatRoom extends Model
{
    protected $fillable = [
        'authority_user_id',
        'agent_user_id',
    ];

    use HasFactory;
}
