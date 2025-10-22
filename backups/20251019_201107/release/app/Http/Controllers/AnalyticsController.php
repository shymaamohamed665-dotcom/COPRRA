<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BehaviorAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly BehaviorAnalysisService $behaviorAnalysisService) {}

    public function userAnalytics(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $analytics = $this->behaviorAnalysisService->getUserAnalytics($user);

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    public function siteAnalytics(): JsonResponse
    {
        $analytics = $this->behaviorAnalysisService->getSiteAnalytics();

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    public function trackBehavior(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|max:50',
            'data' => 'nullable|array',
        ]);

        if (! is_array($validated)) {
            return response()->json(['error' => 'Invalid validation result'], 400);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $actionValue = $validated['action'] ?? '';
        $action = is_string($actionValue) ? $actionValue : '';

        /** @var array<string, string|int|float|bool|array|null> $dataValue */
        $dataValue = $validated['data'] ?? [];
        $data = is_array($dataValue) ? $dataValue : [];

        $this->behaviorAnalysisService->trackUserBehavior(
            $user,
            $action,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل السلوك بنجاح',
        ]);
    }
}
