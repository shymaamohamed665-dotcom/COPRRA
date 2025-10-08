<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;

class DocumentationController extends BaseApiController
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => Date::now()->toISOString(),
            'version' => '1.0.0',
        ]);
    }
}
