<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\Auth\Guard;

class ReviewController extends Controller
{
    // تم إضافة هذا الثابت لتقليل التكرار
    private const UNAUTHORIZED_MESSAGE = 'Unauthorized action.';

    /**
     * Show the form for creating a new resource.
     */
    public function create(Product $product, Guard $auth): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        // التحقق مما إذا كان المستخدم قد قام بمراجعة هذا المنتج بالفعل
        $existingReview = $product->reviews()->where('user_id', $auth->id())->exists();

        if ($existingReview) {
            return redirect()->route('products.show', $product->id)
                ->with('error', 'You have already reviewed this product.');
        }

        /** @var view-string $view */
        $view = 'reviews.create';

        return view($view, ['product' => $product]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReviewRequest $request, Product $product): \Illuminate\Http\JsonResponse
    {
        // Check if user already reviewed this product
        $existingReview = $product->reviews()->where('user_id', auth()->id())->exists();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this product.'], 422);
        }

        // Sanitize the comment to prevent XSS
        /** @var string $comment */
        $comment = $request->comment;
        $sanitizedComment = strip_tags($comment);

        $review = Review::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'content' => $sanitizedComment,
        ]);

        return response()->json($review, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReviewRequest $request, Review $review, Guard $auth): \Illuminate\Http\RedirectResponse
    {
        // التحقق من أن المستخدم هو صاحب المراجعة
        if ($review->user_id !== $auth->id()) {
            abort(403, self::UNAUTHORIZED_MESSAGE); // تم استخدام الثابت هنا
        }

        $review->update($request->validated());

        return redirect()->route('products.show', $review->product_id)
            ->with('success', 'Review updated successfully!');
    }
}
