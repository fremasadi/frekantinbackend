<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SellerEarning; // ⬅️ Tambahkan baris ini
use App\Models\Order;
use Carbon\Carbon;

class SellerEarningController extends Controller
{
    public function getByAuthSeller(Request $request)
    {
        $user = $request->user(); // User yang login lewat Sanctum

        $earnings = SellerEarning::where('seller_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $earnings,
        ]);
    }

    public function getOrdersByEarning($id)
{
    $earning = SellerEarning::findOrFail($id);

    $month = Carbon::parse($earning->month)->format('m');
    $year = Carbon::parse($earning->month)->format('Y');

    $orders = Order::where('seller_id', $earning->seller_id)
        ->whereMonth('created_at', $month)
        ->whereYear('created_at', $year)
        ->where('order_status', 'COMPLETED')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $orders,
    ]);
}
}
