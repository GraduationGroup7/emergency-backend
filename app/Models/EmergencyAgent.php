<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyAgent extends Model
{
    protected $fillable = ['emergency_id', 'agent_id'];
    use HasFactory;
}
