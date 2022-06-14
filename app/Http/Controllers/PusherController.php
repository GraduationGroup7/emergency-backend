<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PusherController extends Controller
{
    public function auth(Request $request): JsonResponse
    {
        $user = Auth::user();
        return res(['auth' => "COOL", 'user' => $user]);
    }
}
