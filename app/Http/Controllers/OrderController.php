<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\CoreApi;
use Illuminate\Support\Facades\Log;
use App\Enums\OrderStatus;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

class OrderController extends Controller
{
    private $firebaseDatabase;

    public function __construct()
    {
        $this->firebaseDatabase = (new Factory)
        ->withServiceAccount(config('firebase.firebase.service_account'))
        ->withDatabaseUri('https://fre-kantin-default-rtdb.firebaseio.com') // Pastikan menggunakan URL yang benar
        ->createDatabase();


        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_ENV') === 'production';
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function generateOrderId()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $randomString = '';
            for ($i = 0; $i < 7; $i++) {
                $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
            }
            $existingOrder = Payment::where('payment_gateway_reference_id', $randomString)->exists();
        } while ($existingOrder);

        return $randomString;
    }

    public function createOrder(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('customer_id', $user->id)->firstOrFail();
        $cartItems = CartItem::where('cart_id', $cart->id)->get();
    
        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Cart items not found'], 404);
        }
    
        // Cek apakah table_number ada dan statusnya aktif
        $tableNumber = $request->table_number;
        $table = DB::table('table_numbers')
            ->where('number', $tableNumber)
            ->where('status', 1) // Pastikan status aktif
            ->first();
    
        if (!$table) {
            return response()->json([
                'status' => false,
                'message' => 'Tuliskan Nomer Meja Dengan Benar',
            ], 400);
        }
    
        $product = $cartItems->first()->product;
        $sellerId = $product->seller_id;
        $orderId = $this->generateOrderId();
        $totalAmount = $this->calculateTotalAmount($cart->id);
    
        DB::beginTransaction();
    
        try {
            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'seller_id' => $sellerId,
                'order_id' => $orderId,
                'order_status' => OrderStatus::PENDING->value,
                'total_amount' => $totalAmount,
                'table_number' => $tableNumber, // Gunakan table_number yang sudah divalidasi
                'estimated_delivery_time' => Carbon::now()->addMinutes(30),
            ]);
    
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'notes' => $item->notes ?? '',
                ]);
            }
    
            CartItem::where('cart_id', $cart->id)->delete();
    
            $this->firebaseDatabase
                ->getReference('notifications/orders')
                ->push([
                    'order_id' => $orderId,
                    'customer_id' => $cart->customer_id,
                    'seller_id' => $sellerId,
                    'total_amount' => $totalAmount,
                    'status' => OrderStatus::PENDING->value,
                    'timestamp' => Carbon::now()->timestamp,
                ]);
    
            $paymentType = $request->payment_type;
            $bank = $request->bank;
    
            if ($paymentType === 'BANK_TRANSFER' && !$bank) {
                return response()->json(['status' => false, 'message' => 'Bank is required for bank transfer payment'], 400);
            }
    
            $paymentGatewayResponse = $this->processPayment($paymentType, $totalAmount, $orderId, $bank);
    
            if (isset($paymentGatewayResponse['error'])) {
                throw new \Exception($paymentGatewayResponse['error']);
            }
    
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_status' => OrderStatus::PENDING->value,
                'payment_type' => $paymentType,
                'payment_gateway' => 'midtrans',
                'payment_gateway_reference_id' => $orderId,
                'payment_gateway_response' => json_encode($paymentGatewayResponse['response']),
                'gross_amount' => $totalAmount,
                'payment_proof' => null,
                'payment_date' => Carbon::now(),
                'expired_at' => Carbon::now()->addHours(1),
                'payment_va_name' => $paymentGatewayResponse['va_bank'],
                'payment_va_number' => $paymentGatewayResponse['va_number'],
            ]);
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'order' => $order,
                'payment' => $payment,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error creating order or payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function processPayment($paymentType, $totalAmount, $orderId, $bank = null)
{
    $transaction_details = [
        'order_id' => $orderId,
        'gross_amount' => $totalAmount,
    ];

    $item_details = [
        [
            'id' => 'item-1',
            'price' => $totalAmount,
            'quantity' => 1,
            'name' => 'Order #' . $orderId,
        ]
    ];

    $customer_details = [
        'first_name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'phone' => auth()->user()->phone ?? 'N/A',
    ];

    // Add custom expiry
    $custom_expiry = [
        'expiry_duration' => 1, // Duration in hours
        'unit' => 'hour', // Units can be 'minute', 'hour', or 'day'
    ];

    $transaction_data = [
        'payment_type' => 'bank_transfer',
        'transaction_details' => $transaction_details,
        'item_details' => $item_details,
        'customer_details' => $customer_details,
        'custom_expiry' => $custom_expiry, // Add this line
    ];

    if ($paymentType === 'BANK_TRANSFER') {
        $transaction_data['bank_transfer'] = [
            'bank' => strtolower($bank)
        ];
    }

    try {
        $response = CoreApi::charge($transaction_data);

        $result = [
            'response' => $response,
            'va_bank' => null,
            'va_number' => null,
            'redirect_url' => null
        ];

        if ($response->payment_type === 'bank_transfer') {
            if (isset($response->va_numbers) && !empty($response->va_numbers)) {
                $result['va_bank'] = $response->va_numbers[0]->bank;
                $result['va_number'] = $response->va_numbers[0]->va_number;
            } elseif (isset($response->permata_va_number)) {
                $result['va_bank'] = 'permata';
                $result['va_number'] = $response->permata_va_number;
            }
        }

        return $result;
    } catch (\Exception $e) {
        Log::error('Midtrans payment processing failed: ' . $e->getMessage());
        return ['error' => 'Payment processing failed: ' . $e->getMessage()];
    }
}


    private function calculateTotalAmount($cartId)
{
    $subtotal = CartItem::where('cart_id', $cartId)
        ->join('products', 'cart_items.product_id', '=', 'products.id')
        ->sum(DB::raw('products.price * cart_items.quantity'));
    
    $serviceFee = 3000.00;
    
    return number_format($subtotal + $serviceFee, 2, '.', '');
}

public function getUserOrders(Request $request)
{
    // Ambil pengguna dari token
    $user = $request->user();

    // Ambil semua order berdasarkan customer_id
    $orders = Order::with([
        'orderItems.product', // Include relasi ke orderItems dan produk
        'payment'            // Include data pembayaran
    ])
    ->where('customer_id', $user->id)
    ->orderBy('created_at', 'desc')
    ->get();

    if ($orders->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No orders found for this user',
            'orders' => []
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'Orders retrieved successfully',
        'orders' => $orders
    ], 200);
}


}