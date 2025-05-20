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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:15', // tambahkan validasi untuk phone jika perlu
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Update data user
        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }
    
        // Lakukan update hanya jika ada data yang berubah
        if (!empty($updateData)) {
            $user->update($updateData);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Data Berhasil DiUpdate',
            'data' => $user->fresh(), // Mengambil data terbaru dari database
        ], 200);
    }


    public function updatePassword(Request $request)
{
    $user = $request->user(); // pengguna yang login via token

    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Verifikasi password lama
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'status' => false,
            'message' => 'Password lama tidak sesuai.',
        ], 403);
    }

    // Simpan password baru
    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json([
        'status' => true,
        'message' => 'Password berhasil diperbarui.',
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
