<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3Service
{
    public function getFile(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fileData = Storage::disk('s3')->get($filename);

        $headers = [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];

        return response()->stream(function () use ($fileData) {
            echo $fileData;
        }, 200, $headers);
    }
}
