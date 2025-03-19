<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableNumberController extends Controller
{
    public function checkTableNumber(Request $request)
    {
        // Validasi input
        $request->validate([
            'table_number' => 'required|string',
        ]);

        $tableNumber = $request->table_number;

        // Cek apakah table_number ada dan statusnya aktif
        $table = DB::table('table_numbers')
            ->where('number', $tableNumber)
            ->where('status', 1) // Pastikan status aktif
            ->first();

        if (!$table) {
            return response()->json([
                'status' => false,
                'message' => 'Periksa Nomer Meja Dengan Benar',
            ], 404);
        }

        return response()->json([
            'status' => true,
        ], 200);
    }
}
