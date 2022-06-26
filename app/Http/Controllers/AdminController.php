<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            Log::info('BACKUP TIME ' . Carbon::now()->format('Y-m-d H-i-s'));
            Artisan::call('backup:run', ['--disable-notifications' => true]);
            return res('Backup job has been dispatched');
        } catch (\Exception $exception) {
            return res($exception->getMessage(), 500);
        }
    }
}
