<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellerEarningController extends Controller
{
    public function getByAuthSeller(Request $request)
{
    $user = $request->user(); // User yang login lewat token

    $earnings = SellerEarning::where('seller_id', $user->id)->get();

    return response()->json([
        'success' => true,
        'data' => $earnings,
    ]);
}
}
