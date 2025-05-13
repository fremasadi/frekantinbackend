<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    // Fetch data user yang sedang login
    public function index(Request $request)
{
    $user = $request->user(); // Mendapatkan data pengguna dari token Sanctum

    // Menambahkan URL lengkap untuk gambar profil jika ada
    if ($user->image) {
        $user->image = url('storage/' . $user->image); // Membangun URL lengkap
    }

    return response()->json([
        'status' => true,
        'data' => $user,
    ], 200);
}


    // Update data user yang sedang login
    public function update(Request $request)
    {
        $user = $request->user(); // Mendapatkan data pengguna dari token Sanctum

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:15',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update data user
        $user->username = $request->username ?? $user->username;
        $user->email = $request->email ?? $user->email;
        $user->phone = $request->phone ?? $user->phone;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully',
            'data' => $user,
        ], 200);
    }

     // Ambil status aktif user
     public function getStatus(Request $request)
     {
         $user = Auth::user(); // Ambil user dari token sanctum
 
         return response()->json([
             'status' => true,
             'is_active' => (bool) $user->is_active,
         ]);
     }
 
     // Update status aktif user
     public function updateStatus(Request $request)
     {
         $request->validate([
             'is_active' => 'required|boolean',
         ]);
 
         $user = Auth::user(); // Ambil user dari token sanctum
         $user->is_active = $request->is_active;
         $user->save();
 
         return response()->json([
             'status' => true,
             'message' => 'User active status updated successfully.',
             'is_active' => (bool) $user->is_active,
         ]);
     }
}
