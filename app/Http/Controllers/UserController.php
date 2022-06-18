<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsers(Request $request): JsonResponse
    {
        return res(User::query()->paginate($request->input('perPage') ?? 15));
    }
}
