<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
}
