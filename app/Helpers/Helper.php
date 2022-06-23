<?php

use Illuminate\Http\JsonResponse;


/**
 * @param array|Exception $data
 * @param string $code
 * @param int $status
 * @param array $headers
 *
 * @return JsonResponse
 */
if (!function_exists('res')) {
    function res($data = [], int $httpStatus = 200, array $headers = []): JsonResponse
    {
        if ($data instanceof Illuminate\Support\Collection) {
            $data = $data->toArray();
        }

        return response()->json(
            [
                'status' => 'success',
                'data' => $data
            ],
            $httpStatus,
            $headers
        );
    }
}

if(!function_exists('compareWithEnum')) {
    function compareWithEnum($value, $enum): bool
    {
        return strtoupper($value) == $enum->name;
    }
}

function kaantable (\Illuminate\Database\Eloquent\Builder $query, $request)
{
    $orderBy = $request->input('orderBy', null);
    $orderByDirection = $request->input('orderByDirection', null);

    if($orderBy) {
        \Illuminate\Support\Facades\Log::info('orderBy: ' . $orderBy);
        \Illuminate\Support\Facades\Log::info('orderByDirection: ' . $orderByDirection);

        $query = $query->orderBy($orderBy, $orderByDirection);
    }

    return $query->paginate($request->input('perPage', 15));
}
