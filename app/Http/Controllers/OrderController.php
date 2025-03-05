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
use Midtrans\Snap; // Add this import
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

        $product = $cartItems->first()->product;
        $sellerId = $product->seller_id;
        $orderId = $this->generateOrderId();
        $totalAmount = $this->calculateTotalAmount($cart->id);
        $paymentType = $request->input('payment_type', 'BANK_TRANSFER');
        $bank = $request->input('bank');

        DB::beginTransaction();

        try {
            // Validate payment method
            $this->validatePaymentMethod($paymentType, $bank);

            // Create Order
            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'seller_id' => $sellerId,
                'order_id' => $orderId,
                'order_status' => OrderStatus::PENDING->value,
                'total_amount' => $totalAmount,
                'table_number' => $request->input('table_number'),
                'estimated_delivery_time' => Carbon::now()->addMinutes(30),
            ]);

            // Create Order Items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'notes' => $item->notes ?? '',
                ]);
            }

            // Process Payment with Multiple Strategies
            $paymentResponse = $this->processPaymentWithFallback(
                $paymentType,
                $totalAmount,
                $orderId,
                $bank
            );

            // Handle payment response errors
            if (isset($paymentResponse['error'])) {
                throw new \Exception($paymentResponse['error']);
            }

            // Create Payment Record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_status' => OrderStatus::PENDING->value,
                'payment_type' => $paymentType,
                'payment_gateway' => 'midtrans',
                'payment_gateway_reference_id' => $orderId,
                'payment_gateway_response' => json_encode($paymentResponse['response'] ?? []),
                'gross_amount' => $totalAmount,
                'payment_proof' => null,
                'payment_date' => Carbon::now(),
                'expired_at' => Carbon::now()->addHours(1),
                'payment_va_name' => $paymentResponse['va_bank'] ?? null,
                'payment_va_number' => $paymentResponse['va_number'] ?? null,
            ]);

            // Clear Cart
            CartItem::where('cart_id', $cart->id)->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'order' => $order,
                'payment' => $payment,
                'va_details' => [
                    'bank' => $paymentResponse['va_bank'],
                    'number' => $paymentResponse['va_number']
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Order Creation Detailed Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_type' => $paymentType,
                'bank' => $bank,
                'total_amount' => $totalAmount,
                'order_id' => $orderId
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Order creation failed',
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    private function processPaymentWithFallback($paymentType, $totalAmount, $orderId, $bank)
    {
        $strategies = [
            'coreApi' => [$this, 'processPaymentViaCoreApi'],
            'snap' => [$this, 'processPaymentViaSnap']
        ];

        foreach ($strategies as $strategyName => $strategy) {
            try {
                Log::info("Attempting payment via $strategyName", [
                    'order_id' => $orderId,
                    'amount' => $totalAmount,
                    'bank' => $bank
                ]);

                $result = call_user_func(
                    $strategy,
                    $paymentType,
                    $totalAmount,
                    $orderId,
                    $bank
                );

                if ($result['va_bank'] && $result['va_number']) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning("Payment strategy $strategyName failed", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        throw new \Exception('All payment strategies failed. Unable to process payment.');
    }

    private function processPaymentViaCoreApi($paymentType, $totalAmount, $orderId, $bank)
    {
        $transaction_details = [
            'order_id' => $orderId,
            'gross_amount' => $totalAmount,
        ];

        $item_details = [[
            'id' => 'item-1',
            'price' => $totalAmount,
            'quantity' => 1,
            'name' => 'Order #' . $orderId,
        ]];

        $customer_details = [
            'first_name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone ?? 'N/A',
        ];

        $transaction_data = [
            'payment_type' => 'bank_transfer',
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
            'custom_expiry' => [
                'expiry_duration' => 1,
                'unit' => 'hour',
            ],
            'bank_transfer' => [
                'bank' => strtolower($bank)
            ]
        ];

        $response = CoreApi::charge($transaction_data);

        $result = [
            'response' => $response,
            'va_bank' => null,
            'va_number' => null
        ];

        // VA Number Extraction Logic
        if ($response->payment_type === 'bank_transfer') {
            if (isset($response->va_numbers[0]->bank) && isset($response->va_numbers[0]->va_number)) {
                $result['va_bank'] = $response->va_numbers[0]->bank;
                $result['va_number'] = $response->va_numbers[0]->va_number;
            } elseif (isset($response->permata_va_number)) {
                $result['va_bank'] = 'permata';
                $result['va_number'] = $response->permata_va_number;
            }
        }

        if (!$result['va_bank'] || !$result['va_number']) {
            throw new \Exception('Failed to generate VA number via CoreApi');
        }

        return $result;
    }

    private function processPaymentViaSnap($paymentType, $totalAmount, $orderId, $bank)
    {
        $transaction_details = [
            'order_id' => $orderId,
            'gross_amount' => $totalAmount,
        ];

        $item_details = [[
            'id' => 'item-1',
            'price' => $totalAmount,
            'quantity' => 1,
            'name' => 'Order #' . $orderId,
        ]];

        $customer_details = [
            'first_name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone ?? 'N/A',
        ];

        $transaction_data = [
            'transaction_details' => $transaction_details,
            'item_details' => $item_details,
            'customer_details' => $customer_details,
        ];

        $snapToken = Snap::getSnapToken($transaction_data);

        return [
            'response' => $snapToken,
            'va_bank' => $bank,
            'va_number' => $snapToken
        ];
    }
    private function validatePaymentMethod(string $paymentType, ?string $bank)
    {
        $allowedBanks = ['bni', 'bca', 'mandiri', 'permata'];

        if ($paymentType === 'BANK_TRANSFER') {
            if (!$bank) {
                throw new \InvalidArgumentException('Bank is required for bank transfer');
            }

            if (!in_array(strtolower($bank), $allowedBanks)) {
                throw new \InvalidArgumentException('Unsupported bank for transfer');
            }
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
