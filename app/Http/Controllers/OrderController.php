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
    $cartItems = CartItem::where('cart_id', $cart->id)->with('product')->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['status' => false, 'message' => 'Cart items not found'], 404);
    }

    $tableNumber = $request->table_number;
    $table = DB::table('table_numbers')->where('number', $tableNumber)->where('status', 1)->first();

    if (!$table) {
        return response()->json(['status' => false, 'message' => 'Tuliskan Nomer Meja Dengan Benar'], 400);
    }

    $mainOrderId = $this->generateOrderId();
    $itemsBySeller = $cartItems->groupBy(fn ($item) => $item->product->seller_id);

    DB::beginTransaction();

    try {
        $createdOrders = [];
        $totalAmountAll = 0;

        foreach ($itemsBySeller as $sellerId => $sellerItems) {
            $sellerTotalAmount = $this->calculateSellerTotalAmount($sellerItems);
            $totalAmountAll += $sellerTotalAmount;

            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'seller_id' => $sellerId,
                'order_id' => $mainOrderId,
                'order_status' => OrderStatus::PENDING->value,
                'total_amount' => $sellerTotalAmount,
                'table_number' => $tableNumber,
                'estimated_delivery_time' => Carbon::now()->addMinutes(30),
            ]);

            foreach ($sellerItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'notes' => $item->notes ?? '',
                ]);
            }

            $this->firebaseDatabase->getReference('notifications/orders')->push([
                'order_id' => $mainOrderId,
                'customer_id' => $cart->customer_id,
                'seller_id' => $sellerId,
                'total_amount' => $sellerTotalAmount,
                'status' => OrderStatus::PENDING->value,
                'timestamp' => Carbon::now()->timestamp,
            ]);

            $createdOrders[] = $order;
        }

        CartItem::where('cart_id', $cart->id)->delete();

        // ✅ Validasi pembayaran
        $paymentType = $request->payment_type;
        $bank = $request->bank;

        if ($paymentType === 'BANK_TRANSFER' && !$bank) {
            return response()->json(['status' => false, 'message' => 'Bank is required for bank transfer payment'], 400);
        }

        if (!in_array($paymentType, ['BANK_TRANSFER', 'GOPAY', 'QRIS'])) {
            return response()->json(['status' => false, 'message' => 'Unsupported payment type'], 400);
        }

        // ✅ Proses ke payment gateway
        $paymentGatewayResponse = $this->processPayment($paymentType, $totalAmountAll, $mainOrderId, $bank);

        if (isset($paymentGatewayResponse['error'])) {
            throw new \Exception($paymentGatewayResponse['error']);
        }

        // ✅ Simpan ke tabel Payment
        $paymentData = [
            'order_id' => $createdOrders[0]->id,
            'payment_status' => OrderStatus::PENDING->value,
            'payment_type' => $paymentType,
            'payment_gateway' => 'midtrans',
            'payment_gateway_reference_id' => $mainOrderId,
            'payment_gateway_response' => json_encode($paymentGatewayResponse['response']),
            'gross_amount' => $totalAmountAll,
            'payment_proof' => null,
            'payment_date' => Carbon::now(),
            'expired_at' => Carbon::now()->addHours(1),
        ];

        if ($paymentType === 'BANK_TRANSFER') {
            $paymentData['payment_va_name'] = $paymentGatewayResponse['va_bank'] ?? null;
            $paymentData['payment_va_number'] = $paymentGatewayResponse['va_number'] ?? null;
        } elseif (in_array($paymentType, ['GOPAY', 'QRIS'])) {
            $paymentData['payment_va_name'] = null;
            $paymentData['payment_va_number'] = null;
            $paymentData['payment_qr_url'] = $paymentGatewayResponse['qr_url'] ?? null;
            $paymentData['payment_deeplink'] = $paymentGatewayResponse['deeplink_url'] ?? null;
        }

        $payment = Payment::create($paymentData);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'main_order_id' => $mainOrderId,
            'orders' => $createdOrders,
            'payment' => $payment,
        ], 200);

    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Order creation failed: ' . $e->getMessage());
        return response()->json(['status' => false, 'message' => 'Error creating order or payment: ' . $e->getMessage()], 500);
    }
}


private function calculateSellerTotalAmount($sellerItems)
{
    $subtotal = $sellerItems->sum(function ($item) {
        return $item->product->price * $item->quantity;
    });

    $serviceFee = 3000.00;

    return number_format($subtotal + $serviceFee, 2, '.', '');
}


private function processPayment($paymentType, $amount, $orderId, $bank = null)
{
    try {
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
        ];

        if ($paymentType === 'BANK_TRANSFER') {
            $params['payment_type'] = 'bank_transfer';
            $params['bank_transfer'] = ['bank' => $bank];
        } elseif ($paymentType === 'GOPAY') {
            $params['payment_type'] = 'gopay';
            $params['gopay'] = ['enable_callback' => true, 'callback_url' => 'yourapp://callback'];
        } elseif ($paymentType === 'QRIS') {
            $params['payment_type'] = 'qris';
        }

        $response = \Midtrans\CoreApi::charge($params);

        $result = [
            'response' => $response,
        ];

        if ($paymentType === 'BANK_TRANSFER') {
            $va = $response->va_numbers[0] ?? null;
            $result['va_bank'] = $va->bank ?? null;
            $result['va_number'] = $va->va_number ?? null;
        } elseif ($paymentType === 'GOPAY') {
            $result['qr_url'] = $response->actions[0]->url ?? null;
            $result['deeplink_url'] = $response->actions[1]->url ?? null;
        } elseif ($paymentType === 'QRIS') {
            $result['qr_url'] = $response->actions[0]->url ?? null;
        }
        

        return $result;

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
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