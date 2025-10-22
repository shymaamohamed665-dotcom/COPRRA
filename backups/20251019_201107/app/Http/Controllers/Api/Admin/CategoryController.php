<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => $category,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        /** @var array<string, string> $validatedData */
        $validatedData = is_array($validated) ? $validated : [];
        $category = Category::create($validatedData);

        return response()->json([
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        /** @var array<string, string> $validatedData */
        $validatedData = is_array($validated) ? $validated : [];
        $category->update($validatedData);

        return response()->json(['data' => $category]);
    }

    public function destroy(Category $category): \Illuminate\Http\Response
    {
        $category->delete();

        return response()->noContent();
    }
}
