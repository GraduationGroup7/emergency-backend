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

    public function getMessages($limit=25): Collection|array
    {
        return ChatMessage::query()
            ->where('chat_room_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)->get();
    }
}
