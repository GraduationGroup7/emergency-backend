<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authority extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'authority_type_id',
    ];
    use HasFactory;

    public function user(): Model|Collection|Builder|array|null
    {
        return User::query()->find($this->user_id);
    }
}
