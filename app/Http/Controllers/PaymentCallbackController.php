<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PaymentCallbackController extends Controller
{
    private $database;
    private $messaging;

    public function __construct()
    {
        $firebase = (new Factory)
            ->withServiceAccount(config('firebase.firebase.service_account'))
            ->withDatabaseUri('https://fre-kantin-default-rtdb.firebaseio.com');

        $this->database = $firebase->createDatabase();
        $this->messaging = $firebase->createMessaging();
    }

    private function sendNotificationToSeller($order, $transactionStatus)
    {
        try {
            // Ambil data seller berdasarkan seller_id
            $seller = User::find($order->seller_id);
            
            if (!$seller || !$seller->fcm_token) {
                Log::warning("Seller not found or FCM token not available for seller_id: {$order->seller_id}");
                return;
            }

            // Siapkan pesan notifikasi
            $message = CloudMessage::withTarget('token', $seller->fcm_token)
                ->withNotification(Notification::create(
                    'Ada Pesanan baru nih telah dibayar',
                    "Mohon Segera Proses Dan Antar Ke meja"
                ))
                ->withData([
                    'order_id' => $order->order_id,
                    'status' => $transactionStatus,
                    'total_amount' => (string)$order->total_amount,
                    'customer_name' => $order->customer->name ?? 'Customer',
                    'table_number' => $order->table_number ?? '-',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'order_paid'
                ]);

            // Kirim notifikasi
            $this->messaging->send($message);
            
            Log::info("Notification sent to seller {$seller->id} for order {$order->order_id}");
            
        } catch (\Exception $e) {
            Log::error("Error sending notification: " . $e->getMessage());
        }
    }


public function handle(Request $request)
{
    // Log semua data yang diterima untuk debugging
    Log::info('Midtrans Callback received:', $request->all());
    
    try {
        // Ambil raw body untuk signature validation
        $notification = json_decode($request->getContent(), true);
        
        // Validasi data yang diperlukan ada
        if (!isset($notification['order_id']) || !isset($notification['transaction_status'])) {
            Log::error('Missing required notification data');
            return response('Missing required data', 400);
        }
        
        $orderId = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $fraudStatus = $notification['fraud_status'] ?? 'accept';
        
        // Cari payment
        $payments = Payment::where('payment_gateway_reference_id', $orderId)->get();
        if ($payments->isEmpty()) {
            Log::warning("Payment not found for order_id: $orderId");
            return response('Payment not found', 404);
        }

        // Validasi signature - PERBAIKAN UTAMA
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $signatureKey = hash('sha512', 
            $orderId . 
            $notification['status_code'] . 
            $notification['gross_amount'] . 
            $serverKey
        );

        if ($notification['signature_key'] !== $signatureKey) {
            Log::error('Invalid signature', [
                'expected' => $signatureKey,
                'received' => $notification['signature_key'],
                'order_id' => $orderId
            ]);
            return response('Invalid signature', 403);
        }

        Log::info("Processing payment for order_id: $orderId, status: $transactionStatus");

        // Proses berdasarkan status
        $paymentStatus = 'PENDING';
        $orderStatus = 'PENDING';

        if ($fraudStatus == 'accept') {
            switch ($transactionStatus) {
                case 'capture':
                case 'settlement':
                    $paymentStatus = 'SUCCESS';
                    $orderStatus = 'PAID';
                    break;
                case 'pending':
                    $paymentStatus = 'PENDING';
                    $orderStatus = 'PENDING';
                    break;
                case 'deny':
                case 'expire':
                case 'cancel':
                    $paymentStatus = 'FAILED';
                    $orderStatus = 'CANCELLED';
                    break;
                default:
                    $paymentStatus = 'FAILED';
                    $orderStatus = 'FAILED';
            }
        } else {
            // Jika fraud status bukan accept
            $paymentStatus = 'FAILED';
            $orderStatus = 'CANCELLED';
        }

        // Update payments dan orders
        foreach ($payments as $payment) {
            $payment->update([
                'payment_status' => $paymentStatus,
                'payment_date' => now(),
            ]);

            $order = $payment->order;
            if ($order) {
                $order->update([
                    'order_status' => $orderStatus
                ]);

                // Kirim notifikasi ke seller jika pembayaran berhasil
                if ($paymentStatus === 'SUCCESS') {
                    $this->sendNotificationToSeller($order, $transactionStatus);
                }
            }
        }

        // Update Firebase
        try {
            $ordersRef = $this->database->getReference('notifications/orders');
            $query = $ordersRef->orderByChild('order_id')->equalTo($orderId);
            $snapshot = $query->getSnapshot();

            if ($snapshot->exists()) {
                foreach ($snapshot->getValue() as $key => $orderData) {
                    $ordersRef->getChild($key)->update([
                        'status' => $orderStatus,
                        'updated_at' => time()
                    ]);
                }
            } else {
                Log::warning("Order $orderId not found in Firebase");
            }
        } catch (\Exception $e) {
            Log::error('Error updating Firebase: ' . $e->getMessage());
            // Jangan return error di sini, biarkan tetap sukses untuk Midtrans
        }

        Log::info("Payment callback processed successfully for order_id: $orderId");
        
        // PENTING: Return 200 OK untuk Midtrans
        return response('OK', 200);

    } catch (\Exception $e) {
        Log::error('Callback processing error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        
        // Tetap return 200 agar Midtrans tidak retry terus
        return response('Error processed', 200);
    }
}

}