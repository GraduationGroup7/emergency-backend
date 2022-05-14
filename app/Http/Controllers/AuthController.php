<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

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

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'type' => [new Enum(UserTypeEnum::class)],
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $user = new User([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'type' => $request->type,
            ]);

            $user->save();

            // If creating an Authority
            if(compareWithEnum($request->type, UserTypeEnum::AUTHORITY)) {
                $payload = [
                    'first_name' => $request->name,
                    'last_name' => $request->name,
                    'user_id' => $user->id,
                ];

                AuthorityController::createAuthority($payload);
            }

            else if(compareWithEnum($request->type, UserTypeEnum::AGENT)) {
                $payload = [
                    'first_name' => $request->name,
                    'last_name' => $request->name,
                    'user_id' => $user->id,
                ];

                AgentController::createAgent($payload);
            }

            else if(compareWithEnum($request->type, UserTypeEnum::USER)) {
                $payload = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'user_id' => $user->id,
                    'dob' => Carbon::parse($request->dob),
                ];

                Customer::create($payload);
            }

            DB::commit();

            return res([
                'user' => $user,
                'token' => $user->createToken('Personal Access Token')->plainTextToken,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return res($e->getMessage(), 500);
        }
    }

    public function customer_register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'dob' => 'required|date',
            'password' => 'required|string|confirmed',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $user = new User([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'type' => $request->type,
            ]);

            $user->save();

            Customer::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'user_id' => $user->id,
                'dob' => Carbon::parse($request->dob),
            ]);

            DB::commit();

            return res([
                'user' => $user,
                'token' => $user->createToken('Personal Access Token')->plainTextToken,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return res($e->getMessage(), 500);
        }
    }
}
