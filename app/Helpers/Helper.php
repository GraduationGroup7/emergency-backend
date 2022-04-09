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
