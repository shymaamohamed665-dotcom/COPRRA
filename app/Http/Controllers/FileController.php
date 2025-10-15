<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function show(Request $request, string $path)
    {
        // Middleware 'signed' ensures URL integrity; additionally, never allow path traversal
        $safePath = ltrim(str_replace('..', '', $path), '/');

        if (! Storage::disk('uploads')->exists($safePath)) {
            abort(404);
        }

        $mime = Storage::disk('uploads')->mimeType($safePath) ?: 'application/octet-stream';
        $contents = Storage::disk('uploads')->get($safePath) ?? '';

        $download = (bool) $request->boolean('download', false);
        if ($download) {
            return response($contents, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'attachment; filename="'.basename($safePath).'"',
                'Cache-Control' => 'private, no-transform',
            ]);
        }

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-transform',
        ]);
    }
}
