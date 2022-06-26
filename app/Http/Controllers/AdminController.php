<?php

namespace App\Http\Controllers;

use App\Services\S3Service;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            Artisan::call('backup:run', ['--disable-notifications' => true]);
            $files = Storage::disk('s3')->allFiles('Emergency-Graduation');
            $fileName = explode('/', $files[count($files) - 1])[1];

            return res(['fileName' => $fileName]);
        } catch (\Exception $exception) {
            return res($exception->getMessage(), 500);
        }
    }

    public function getBackup(Request $request, $name): StreamedResponse
    {
        $fileName = 'Emergency-Graduation/' . $name;
        $s3Service = new S3Service();
        return $s3Service->getFile($fileName);
    }

    public function getFileFromS3(Request $request): StreamedResponse
    {
        $fileName = $request->file_name;
        $s3Service = new S3Service();
        return $s3Service->getFile($fileName);
    }
}
