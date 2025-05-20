<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\ImageContentController;
use App\Http\Controllers\TableNumberController;
use App\Http\Controllers\SellerEarningController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Customer login
Route::post('/login/customer', [AuthController::class, 'loginCustomer']);

// Seller login
Route::post('/login/seller', [AuthController::class, 'loginSeller']);

// Admin login
Route::post('/login/admin', [AuthController::class, 'loginAdmin']);

Route::post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);


Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('payment/callback', [PaymentCallbackController::class, 'handle']);




Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/update', [AuthController::class, 'update']);
    

    Route::get('/categories', [CategoryController::class, 'index']); // Menampilkan semua kategori
    Route::get('/products', [ProductController::class, 'index']); // Menampilkan semua produk
    Route::get('/products/category/{categoryId}', [ProductController::class, 'getProductsByCategory']);
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'getReviewsByProduct']);
    Route::get('/products/{id}/average-rating', [ReviewController::class, 'getAverageRatingByProduct']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/rating', [ProductController::class, 'getProductsByRating']);

    Route::get('/user', [UserController::class, 'index']); // Fetch user data
    Route::put('/user', [UserController::class, 'update']); // Update user data

    Route::get('orders/{orderId}/reviews', [ReviewController::class, 'getReviewsByOrderId']);

    // Admin  routes
    Route::middleware('check.role:admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getAllUsers']);
        Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

         // Routes  Category
        Route::post('/categories', [CategoryController::class, 'store']); // Menambahkan kategori
        Route::put('/categories/{id}', [CategoryController::class, 'update']); // Mengupdate kategori
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']); // Menghapus kategori
    });

        // Seller routes
        Route::middleware('check.role:seller')->group(function () {

            //product
            Route::get('seller/products', [ProductController::class, 'indexSellerProducts']); // Menampilkan produk sesuai seller yang login        
            Route::post('/products', [ProductController::class, 'store']); // Menambahkan produk
            Route::put('/products/{id}', [ProductController::class, 'update']); // Mengupdate produk
            Route::delete('/products/{id}', [ProductController::class, 'destroy']); // Menghapus produk

            Route::get('seller/transactions', [TransactionController::class, 'index']); // List transaksi SUCCESS
            Route::get('seller/transactions/{id}', [TransactionController::class, 'show']); // Detail transaksi SUCCESS
            Route::put('seller/transactions/{id}/complete', [TransactionController::class, 'markAsCompleted']); // Ubah status jadi COMPLETED
            Route::get('transaction-summary', [TransactionController::class, 'getTransactionSummary']);
            Route::get('/transaction-summary-by-date', [TransactionController::class, 'getTransactionSummaryByDate']);

            Route::get('/user/status', [UserController::class, 'getStatus']);
            Route::put('/user/status', [UserController::class, 'updateStatus']);

            Route::get('/my-earnings', [SellerEarningController::class, 'getByAuthSeller']);

        });

    // Customer routes
    Route::middleware('check.role:customer')->group(function () {

        //review
        Route::get('/reviews', [ReviewController::class, 'index']); // Menampilkan semua review
        Route::get('/check-review', [ReviewController::class, 'checkReview']);

        Route::post('orders/{orderId}/reviews', [ReviewController::class, 'store']);        
        //cart
        Route::get('/cart', [CartController::class, 'index']); // Melihat keranjang
        Route::get('/cart/total-price', [CartController::class, 'calculateTotalPrice']);



        Route::post('/cart', [CartController::class, 'addToCart']); // Menambahkan item ke keranjang
        Route::put('/cart/items/{id}', [CartController::class, 'updateCartItem']); // Mengupdate jumlah item dalam keranjang
        Route::delete('/cart/items/{id}', [CartController::class, 'removeCartItem']); // Menghapus item dari keranjang

        Route::post('/order', [OrderController::class, 'createOrder']);
        Route::get('/order', [OrderController::class, 'getUserOrders']);

        // Route::post('/midtrans/callback', [OrderController::class, 'handleMidtransCallback']);

        // Route::post('/midtrans/webhook', [PaymentWebhookController::class, 'handleWebhook']);
        // Route::post('midtrans/callback', [MidtransCallbackController::class, 'handle']);
        Route::get('/image-contents', [ImageContentController::class, 'getActiveImages']);

        Route::post('/check-table-number', [TableNumberController::class, 'checkTableNumber']);


    });
});