<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // Menampilkan isi keranjang pelanggan
    public function index()
    {
        $customerId = Auth::id();

        // Cari keranjang milik customer yang sedang login
        $cart = Cart::where('customer_id', $customerId)
            ->with(['items.product'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is empty',
                'data' => []
            ]);
        }

        // Update URL image dari produk yang ada di keranjang
        $cartItems = $cart->items->map(function ($item) {
            $product = $item->product;

            if ($product && $product->image) {
                $product->image = $this->getFullImageUrl($product->image);
            }

            // Tambahkan info seller jika ada
            if ($product && $product->seller) {
                $product->seller_info = [
                    'id' => $product->seller->id,
                    'name' => $product->seller->name,
                    'is_active' => (bool) $product->seller->is_active,
                ];
            }

            return $item;
        });


        return response()->json([
            'status' => true,
            'data' => $cartItems
        ]);
    }

    // Menambahkan item ke dalam keranjang
    public function addToCart(Request $request)
{
    $validator = Validator::make($request->all(), [
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    // Ambil produk dan cek stok
    $product = \App\Models\Product::find($request->product_id);

    if (!$product || $product->stock <= 0) {
        return response()->json([
            'status' => false,
            'message' => 'Product is out of stock'
        ], 400);
    }

    $customerId = Auth::id();
    $cart = Cart::firstOrCreate(['customer_id' => $customerId]);

    // Cari item keranjang berdasarkan `product_id` dan `cart_id`
    $cartItem = CartItem::where('cart_id', $cart->id)
        ->where('product_id', $request->product_id)
        ->first();

    if ($cartItem) {
        // Jika item sudah ada, cek apakah total quantity melebihi stok
        $newQuantity = $cartItem->quantity + $request->quantity;
        if ($newQuantity > $product->stock) {
            return response()->json([
                'status' => false,
                'message' => 'Not enough stock available'
            ], 400);
        }

        $cartItem->quantity = $newQuantity;
        $cartItem->save();
    } else {
        // Jika item belum ada, pastikan quantity tidak melebihi stok
        if ($request->quantity > $product->stock) {
            return response()->json([
                'status' => false,
                'message' => 'Not enough stock available'
            ], 400);
        }

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'notes' => $request->notes ?? null,
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Product added to cart',
        'data' => $cartItem
    ], 201);
}

     // Fungsi untuk menghitung total harga keranjang
     public function calculateTotalPrice()
     {
         $customerId = Auth::id();

         $cart = Cart::where('customer_id', $customerId)
             ->with(['items.product'])
             ->first();

         if (!$cart || $cart->items->isEmpty()) {
             return response()->json([
                 'status' => true,
                 'message' => 'Cart is empty',
                 'total_price' => 0
             ]);
         }

         // Hitung total harga keranjang
         $totalPrice = $cart->items->reduce(function ($total, $item) {
             return $total + ($item->product->price * $item->quantity);
         }, 0);

         return response()->json([
             'status' => true,
             'total_price' => number_format($totalPrice, 2)
         ]);
     }

    // Fungsi utilitas untuk menghasilkan URL lengkap dari gambar
    private function getFullImageUrl($imagePath)
    {
        return request()->getSchemeAndHttpHost() . '/storage/' . $imagePath;
    }

    // Mengupdate jumlah item dalam keranjang
    public function updateCartItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItem = CartItem::findOrFail($id);

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'status' => true,
            'message' => 'Cart item updated successfully',
            'data' => $cartItem
        ], 200);
    }

    // Menghapus item dari keranjang
    public function removeCartItem($id)
    {
        $cartItem = CartItem::findOrFail($id);
        $cartItem->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully'
        ]);
    }

    // Mengupdate catatan (notes) dari item keranjang
    public function updateCartItemNotes(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'notes' => 'nullable|string|max:1000'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    $cartItem = CartItem::with('cart')->findOrFail($id);


    $cartItem->update(['notes' => $request->notes]);

    return response()->json([
        'status' => true,
        'message' => 'Cart item notes updated successfully',
        'data' => $cartItem
    ]);
}



}
