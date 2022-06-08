<?php

namespace App\Http\Controllers;

use App\Models\Authority;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorityController extends Controller
{
    public function getAuthorities(): JsonResponse
    {
        $authorities = Authority::all();
        return res($authorities);
    }

    public function getAuthority(int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        return res($authority);
    }

    public function updateAuthority(Request $request, int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        $validator = validator($request->all(), [
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'user_id' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return res($validator->errors(), 400);
        }

        $authority->update($request->all());
        return res($authority);
    }

    /**
     * @throws Exception
     */
    public static function createAuthority($data)
    {
        $validator = validator($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors());
        }

        return Authority::query()->create($data);
    }

    public function deleteAuthority(int $id): JsonResponse
    {
        $authority = Authority::query()->find($id);
        if (!$authority) {
            return res('Authority not found', 404);
        }

        DB::beginTransaction();
        try {
            $user = User::find($authority->user_id);
            $authority->delete();
            $user->delete();

            DB::commit();
            return res('Authority deleted successfully');
        } catch (Exception $e) {
            return res($e->getMessage(), 400);
        }
    }
}