<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 */
class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::query()->latest()->get();

        return response()->json(['data' => $brands]);
    }

    public function show(Brand $brand): JsonResponse
    {
        return response()->json(['data' => $brand]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        /** @var array<string, string> $validatedData */
        $validatedData = is_array($validated) ? $validated : [];
        $brand = Brand::create($validatedData);

        return response()->json(['data' => $brand], 201);
    }

    public function destroy(Brand $brand): \Illuminate\Http\Response
    {
        // Use force delete to permanently remove the brand record
        // to align with tests that expect the row to be missing.
        $brand->forceDelete();

        return response()->noContent();
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        /** @var array<string, string> $validatedData */
        $validatedData = is_array($validated) ? $validated : [];
        $brand->update($validatedData);

        return response()->json(['data' => $brand]);
    }
}
