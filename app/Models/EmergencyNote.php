<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyNote extends Model
{
    protected $fillable = [
        'emergency_id',
        'user_id',
        'note',
    ];

    use HasFactory;
}
