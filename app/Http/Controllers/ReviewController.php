<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderItem;

class ReviewController extends Controller
{
    // Menampilkan semua review
    public function index()
    {
        $reviews = Review::with('product', 'customer')->get();
        return response()->json([
            'status' => true,
            'data' => $reviews,
        ]);
    }

    public function getReviewsByOrderId($orderId)
    {
        try {
            $reviews = Review::select('reviews.*')
                ->join('order_items', 'reviews.order_item_id', '=', 'order_items.id')
                ->where('order_items.order_id', $orderId)
                ->with(['product', 'customer']) // eager load relations
                ->get();

            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No reviews found for this order'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $reviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    // Menambahkan review baru
    public function store(Request $request, $orderId)
{
    try {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek apakah order milik customer yang login
        $order = Order::where('id', $orderId)
            ->where('customer_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found or unauthorized'
            ], 404);
        }

        // Cek apakah product ada di order_items
        $orderItem = OrderItem::where('order_id', $orderId)
            ->where('product_id', $request->product_id)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found in this order'
            ], 404);
        }

        // Cek apakah sudah pernah review
        $existingReview = Review::where('order_item_id', $orderItem->id)
            ->where('customer_id', auth()->id())
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this item'
            ], 400);
        }

        // Handle image upload jika ada
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('reviews', 'public');
        }

        // Create review
        $review = Review::create([
            'order_item_id' => $orderItem->id,
            'product_id' => $request->product_id,
            'customer_id' => auth()->id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
            'image' => $imagePath,
            'review_date' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully',
            'data' => $review
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to submit review',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function getReviewsByProduct($productId)
    {
        $reviews = Review::where('product_id', $productId)->with('customer')->get();

        if ($reviews->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No reviews found for this product.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $reviews,
        ], 200);
    }

    public function getAverageRatingByProduct($productId)
    {
        // Mengambil semua review untuk produk tertentu
        $reviews = Review::where('product_id', $productId)->get();

        // Menghitung rata-rata rating
        if ($reviews->count() > 0) {
            $averageRating = $reviews->avg('rating');
        } else {
            // Jika tidak ada review, default ke 0
            $averageRating = 0;
        }

        // Mengembalikan respon dengan rata-rata rating
        return response()->json([
            'averageRating' => $averageRating
        ]);
    }
    
    public function checkReview(Request $request)
    {
        $orderItemId = $request->query('order_item_id'); // Ambil dari query string
        $productId = $request->query('product_id'); // Ambil dari query string

        // Validasi input harus ada kedua parameter
        if (!$orderItemId || !$productId) {
            return response()->json([
                'status' => false,
                'message' => 'order_item_id and product_id are required'
            ], 400);
        }

        // Cari review berdasarkan order_item_id DAN product_id
        $review = Review::where('order_item_id', $orderItemId)
            ->where('product_id', $productId)
            ->first();

        if ($review) {
            return response()->json([
                'status' => true,
                'rating' => $review->rating,
                'comment' => $review->comment
            ], 200);
        }

        return response()->json([
            'status' => false
        ], 200);
    }
}
