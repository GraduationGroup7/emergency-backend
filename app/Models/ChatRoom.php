<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = [
        'emergency_id',
        'name',
    ];

    use HasFactory;

    public function getMessages($limit=null): Collection|array
    {
        return ChatMessage::query()
            ->where('chat_room_id', $this->id)
            ->limit($limit)->get();
    }
}
