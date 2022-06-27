<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyFile extends Model
{
    protected $fillable = ['emergency_id', 'name', 'type', 'url', 's3_url'];
    use HasFactory;
}
