<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = request(['email','password']);

        if(!Auth::attempt($credentials))
        {
            return response()->json([
                'message' => 'Incorrect username or password'
            ],400);
        }

        $user = $request->user();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        return response()->json([
            'accessToken'   =>$token,
            'token_type'    => 'Bearer',
            'userData'      => [
                'id'            => $user->id,
                'email'         => $user->email,
                'password'      => $user->password,
            ],
        ]);
    }
}
