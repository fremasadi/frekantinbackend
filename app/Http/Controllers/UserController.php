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
    // Get the authenticated user
    $user = auth()->user();

    // Check if user is authenticated
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized - Please login first',
        ], 401);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
        'phone' => 'sometimes|string|max:20',
        'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors(),
        ], 422);
    }

    if ($request->hasFile('image')) {
        $imageName = time().'.'.$request->image->extension();  
        $request->image->move(public_path('images/users'), $imageName);
        $user->image = $imageName;
    }

    $updateData = [];
    $fields = ['name', 'email', 'phone'];
    
    foreach ($fields as $field) {
        if ($request->has($field)) {
            $updateData[$field] = $request->input($field);
        }
    }

    if (!empty($updateData)) {
        try {
            $user->update($updateData);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Update failed due to server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Data Berhasil DiUpdate',
        'data' => $user->fresh(),
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
