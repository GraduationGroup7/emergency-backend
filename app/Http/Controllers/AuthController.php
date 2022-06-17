<?php

namespace App\Http\Controllers;

use App\Enum\UserTypeEnum;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Authority;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'type'         => $user->type,
                'password'      => $user->password,
            ],
        ]);
    }

    public function verifyPhone(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|string',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }


        $basic  = new \Vonage\Client\Credentials\Basic("47ac5dca", "CUcRGKKwEyqV0AlI");
        $client = new \Vonage\Client(new \Vonage\Client\Credentials\Container($basic));

        try {
            $result = $client->verify()->check($request->request_id, $request->code);

            $customer = Customer::query()->where('user_id', $user->id)->first();
            if($customer) {
                $customer->verified = 1;
                $customer->save();
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
            'phone_number' => 'required|string',
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
                'phone_number' => $request->phone_number,
                'verified' => 0,
            ]);

            $basic  = new \Vonage\Client\Credentials\Basic("47ac5dca", "CUcRGKKwEyqV0AlI");
            $client = new \Vonage\Client(new \Vonage\Client\Credentials\Container($basic));

            $phone_request = new \Vonage\Verify\Request($request->phone_number, "Emergency");
            $response = $client->verify()->start($phone_request);

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
                'request_id' => $response->getRequestId()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info(json_encode($e->getMessage()));
            return res($e->getMessage(), 500);
        }
    }

    public function getUser(Request $request): JsonResponse
    {
        $user = Auth::user();
        $user = array_merge(
            $user->toArray(),
            ['verified' => false]
        );

        if($user['type'] == 'user') {
            $user['verified'] = Customer::query()->where('user_id', $user['id'])->first()->verified;
        }

        return res($user);
    }
}
