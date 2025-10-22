<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 */
class BrandController extends Controller
{
    /**
     * Display a listing of the brands with optional search/sort.
     */
    public function index(Request $request): View
    {
        $query = Brand::query();

        // Search by name
        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $sort = $request->string('sort')->toString() ?: 'id';
        $direction = $request->string('direction')->toString() ?: 'desc';
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }
        $query->orderBy($sort, $direction);

        $brands = $query->paginate(10);

        return view('brands.index', ['brands' => $brands]);
    }

    /**
     * Show the form for creating a new brand.
     */
    public function create(): View
    {
        return view('brands.create');
    }

    /**
     * Store a newly created brand in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:brands,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'logo_url' => ['nullable', 'url', 'max:500'],
        ]);

        Brand::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
            'is_active' => true,
        ]);

        return redirect('/brands');
    }

    /**
     * Display the specified brand.
     */
    public function show(int $id): View
    {
        $brand = Brand::find($id);
        if (! $brand) {
            abort(404);
        }

        return view('brands.show', ['brand' => $brand]);
    }

    /**
     * Show the form for editing the specified brand.
     */
    public function edit(int $id): View
    {
        $brand = Brand::find($id);
        if (! $brand) {
            abort(404);
        }

        return view('brands.edit', ['brand' => $brand]);
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $brand = Brand::find($id);
        if (! $brand) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:brands,slug,'.$brand->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'logo_url' => ['nullable', 'url', 'max:500'],
        ]);

        $brand->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'logo_url' => $validated['logo_url'] ?? null,
        ]);

        return redirect('/brands');
    }

    /**
     * Remove the specified brand from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            // Resolve via container to allow tests to mock Brand::class behavior
            $brandModel = app(Brand::class);
            $brand = $brandModel->findOrFail($id);
            // Use force delete to permanently remove the brand record
            // to align with tests that expect the row to be missing.
            $brand->forceDelete();

            return redirect('/brands');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404);
        } catch (\Throwable $e) {
            abort(500);
        }
    }
}
