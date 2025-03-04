<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    // Menambahkan item ke keranjang
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['customer_id' => $request->user()->id]);

        $cartItem = CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json(['status' => true, 'message' => 'Item added to cart', 'data' => $cartItem], 201);
    }

    // Menampilkan semua item di keranjang customer yang login
    public function index(Request $request)
    {
        $cart = Cart::where('customer_id', $request->user()->id)->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Cart not found'], 404);
        }

        $cartItems = $cart->cartItems()->with('product')->get();

        return response()->json(['status' => true, 'data' => $cartItems]);
    }
}
