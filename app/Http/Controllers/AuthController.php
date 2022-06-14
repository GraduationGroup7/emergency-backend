<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Models\Agent;
use App\Models\Authority;
use App\Models\User;
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

    public function verifyPhone(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|string',
            'code' => 'required|string',
            'type' => 'required|string',
            'id' =>   'required|string'
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }


        $basic  = new \Vonage\Client\Credentials\Basic("47ac5dca", "CUcRGKKwEyqV0AlI");
        $client = new \Vonage\Client(new \Vonage\Client\Credentials\Container($basic));

        try {
            $result = $client->verify()->check($request->request_id, $request->code);
            var_dump($result->getResponseData());

            if(compareWithEnum($request->type, UserTypeEnum::AUTHORITY)) {
                $authority = Authority::query()->find($request->id);
                if ($authority) {
                    $authority->verified = 1;
                    $authority->save();
                }
            }
            else if(compareWithEnum($request->type, UserTypeEnum::AGENT)) {
                $agent = Agent::find($request->id);
                if($agent) {
                    $agent->verified = 1;
                    $agent->save();
                }
            }

            return 'success';
        } catch (\Exception $e) {
            return res($e->getMessage(), 500);
        }

    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'type' => [new Enum(UserTypeEnum::class)],
            'phone' => 'required|numeric',
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
                'phone' => $request->phone,
                'verified' => 0,
            ]);

            $basic  = new \Vonage\Client\Credentials\Basic("47ac5dca", "CUcRGKKwEyqV0AlI");
            $client = new \Vonage\Client(new \Vonage\Client\Credentials\Container($basic));

            $request = new \Vonage\Verify\Request($request->phone, "Emergency");
            $response = $client->verify()->start($request);

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

            DB::commit();

            return res([
                'user' => $user,
                'token' => $user->createToken('Personal Access Token')->plainTextToken,
                'request_id' => $response->getRequestId()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return res($e->getMessage(), 500);
        }
    }
}
