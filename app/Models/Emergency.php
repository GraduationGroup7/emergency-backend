<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emergency extends Model
{
    protected $fillable = [
        'reporting_user_id',
        'approving_authority_id',
        'completed',
        'latitude',
        'longitude'
    ];

    use HasFactory;
}
