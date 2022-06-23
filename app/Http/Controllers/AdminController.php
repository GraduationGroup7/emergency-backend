<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    const TABLE_ROUTES = [
        [
            'name' => 'customers',
            'model' => 'customers',
        ],
        [
            'name' => 'authorities',
            'model' => 'authorities',
        ],
        [
            'name' => 'agents',
            'model' => 'agents',
        ],
        [
            'name' => 'emergencies',
            'model' => 'emergencies',
        ],
    ];

    public function getTableRoutes(Request $request): JsonResponse
    {
        return res(self::TABLE_ROUTES);
    }
}
