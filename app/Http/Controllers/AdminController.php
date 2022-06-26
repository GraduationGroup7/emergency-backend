<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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

        [
            'name' => 'available-agents',
            'model' => 'available-agents',
        ],
    ];

    public function getTableRoutes(Request $request): JsonResponse
    {
        return res(self::TABLE_ROUTES);
    }

    public function takeProjectBackup(Request $request): JsonResponse
    {
        try {
            Artisan::queue('backup:run', ['--disable-notifications' => true]);
            Artisan::call('queue:work');
            return res('Backup job has been dispatched');
        } catch (\Exception $exception) {
            return res($exception->getMessage(), 500);
        }
    }
}
