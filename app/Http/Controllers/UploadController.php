<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadFileRequest;
use App\Services\Security\VirusScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        if (! $file) {
            return response()->json(['message' => 'No file provided'], 422);
        }

        // Security: scan file for viruses and disallowed PHP content
        try {
            /** @var VirusScanner $scanner */
            $scanner = app(VirusScanner::class);
            $scanner->scan($file);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Upload blocked by security policy',
                'error' => $e->getMessage(),
            ], 422);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = $request->sanitizeFilename($file->getClientOriginalName(), $extension);

        // Organize uploads by date to avoid directory bloat
        $datePath = now()->format('Y/m/d');
        $customPath = trim((string) $request->input('path', '')); // optional subdir
        $customPath = $customPath !== '' ? Str::of($customPath)->replace('..', '')->trim('/')->value() : '';
        $targetDir = $customPath !== '' ? $customPath.'/'.$datePath : $datePath;

        $storedPath = Storage::disk('uploads')->putFileAs($targetDir, $file, $safeName);

        // Build signed route URL valid for 10 minutes
        $signedUrl = URL::temporarySignedRoute(
            'files.show',
            now()->addMinutes(10),
            ['path' => $storedPath]
        );

        return response()->json([
            'path' => $storedPath,
            'url' => $signedUrl,
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ], 201);
    }
}
