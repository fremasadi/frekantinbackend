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
    // Log semua request yang masuk
    Log::info('Payment callback received', [
        'headers' => $request->headers->all(),
        'content' => $request->getContent(),
        'method' => $request->method(),
        'url' => $request->fullUrl()
    ]);

    $notification = json_decode($request->getContent(), true);
    
    if (!$notification) {
        Log::error('Invalid JSON in callback');
        return response()->json(['message' => 'Invalid JSON'], 400);
    }

    $orderId = $notification['order_id'] ?? null;
    $transactionStatus = $notification['transaction_status'] ?? null;
    $fraudStatus = $notification['fraud_status'] ?? 'accept';
    
    Log::info('Processing callback', [
        'order_id' => $orderId,
        'transaction_status' => $transactionStatus,
        'fraud_status' => $fraudStatus
    ]);

    if (!$orderId || !$transactionStatus) {
        Log::error('Missing required fields in callback', $notification);
        return response()->json(['message' => 'Missing required fields'], 400);
    }
    
    $payments = Payment::where('payment_gateway_reference_id', $orderId)->get();
    if ($payments->isEmpty()) {
        Log::error('Payment not found for order_id: ' . $orderId);
        return response()->json(['message' => 'Payment not found'], 404);
    }

    // Log payment yang ditemukan
    Log::info('Found payments', [
        'count' => $payments->count(),
        'payment_ids' => $payments->pluck('id')->toArray()
    ]);

    $signatureKey = hash('sha512', 
        $orderId . 
        $notification['status_code'] . 
        $notification['gross_amount'] . 
        env('MIDTRANS_SERVER_KEY')
    );

    if ($notification['signature_key'] !== $signatureKey) {
        Log::error('Invalid signature', [
            'received' => $notification['signature_key'],
            'calculated' => $signatureKey
        ]);
        return response()->json(['message' => 'Invalid signature'], 403);
    }

    Log::info('Signature verified successfully');

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

        Log::info('Status mapping', [
            'transaction_status' => $transactionStatus,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus
        ]);

        foreach ($payments as $payment) {
            $oldStatus = $payment->payment_status;
            
            $payment->update([
                'payment_status' => $paymentStatus,
                'payment_date' => now(),
            ]);

            Log::info('Payment updated', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $paymentStatus
            ]);

            $order = $payment->order;
            if ($order) {
                $oldOrderStatus = $order->order_status;
                
                $order->update([
                    'order_status' => $orderStatus
                ]);

                Log::info('Order updated', [
                    'order_id' => $order->id,
                    'order_reference' => $order->order_id,
                    'old_status' => $oldOrderStatus,
                    'new_status' => $orderStatus
                ]);

                // Kirim notifikasi ke seller jika pembayaran berhasil
                if ($paymentStatus === 'SUCCESS') {
                    Log::info('Sending notification to seller for successful payment');
                    $this->sendNotificationToSeller($order, $transactionStatus);
                }
            } else {
                Log::warning('Order not found for payment_id: ' . $payment->id);
            }
        }
    } else {
        Log::warning('Fraud status not accepted', ['fraud_status' => $fraudStatus]);
    }

    try {
        // Update Firebase Realtime Database
        $ordersRef = $this->database->getReference('notifications/orders');
        $query = $ordersRef->orderByChild('order_id')->equalTo($orderId);
        $snapshot = $query->getSnapshot();

        if ($snapshot->exists()) {
            foreach ($snapshot->getValue() as $key => $orderData) {
                $ordersRef->getChild($key)->update([
                    'status' => $orderStatus,
                    'updated_at' => time()
                ]);
                Log::info('Firebase updated for order: ' . $orderId);
            }
        } else {
            Log::warning("Order $orderId not found in Firebase");
        }
    } catch (\Exception $e) {
        Log::error('Error updating Firebase: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating Firebase'], 500);
    }

    Log::info('Callback processed successfully for order: ' . $orderId);
    return response()->json(['message' => 'Payment status updated']);
}

}