<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    // Fungsi untuk registrasi dengan OTP
    public function register(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|unique:users,username',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'role' => 'required|string|in:admin,seller,customer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'otp' => 'required|string|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verifikasi OTP
        $storedOtp = Cache::get('otp_' . $request->email);
        if (!$storedOtp || $storedOtp != $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'OTP tidak valid atau sudah kadaluarsa'
            ], 400);
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profile-images', 'public');
        }

        // Only allow admin to create admin accounts
        if ($request->role === User::ROLE_ADMIN &&
            (!Auth::user() || !Auth::user()->isAdmin())) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to create admin account'
            ], 403);
        }

        // Create the user
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'image' => $imagePath
        ]);

        // Hapus OTP dari cache
        Cache::forget('otp_' . $request->email);

        // Generate a token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully',
            // 'data' => [
            //     'user' => $user,
            //     'image_url' => $imagePath ? url('storage/' . $imagePath) : null
            // ],
            'token' => $token
        ], 201);
    }

    // Login untuk Customer
    public function loginCustomer(Request $request)
    {
        return $this->loginUser($request, 'customer');
    }

    // Login untuk Seller
    public function loginSeller(Request $request)
    {
        return $this->loginUser($request, 'seller');
    }

    // Login untuk Admin
    public function loginAdmin(Request $request)
    {
        return $this->loginUser($request, 'admin');
    }

    private function loginUser(Request $request, $role)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'sometimes|string'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
    
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'role' => $role])) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
    
            // Simpan FCM Token jika dikirim dari frontend
            if ($request->has('fcm_token')) {
                $user->update(['fcm_token' => $request->fcm_token]);
            }
    
            return response()->json([
                'status' => true,
                'message' => ucfirst($role) . ' login berhasil',
                'token' => $token,
                'user' => $user
            ]);
        }
    
        return response()->json(['status' => false, 'message' => 'Invalid credentials or role'], 401);
    }

    public function updateFcmToken(Request $request)
{
    $validator = Validator::make($request->all(), [
        'fcm_token' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $user = Auth::user();
    $user->update(['fcm_token' => $request->fcm_token]);

    return response()->json([
        'status' => true,
        'message' => 'FCM Token updated successfully'
    ], 200);
}

    

    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|required|string|unique:users,username,' . $user->id,
            'email' => 'sometimes|required|string|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prepare data for update
        $updateData = $request->only(['username', 'email', 'phone']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $imagePath = $request->file('image')->store('profile-images', 'public');
            $updateData['image'] = $imagePath;
        }

        // Hash the password if it's being updated
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update the user
        $user->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully',
            'data' => [
                'user' => $user,
                'image_url' => $user->image ? url('storage/' . $user->image) : null
            ]
        ], 200);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }

    // Admin only methods
    public function getAllUsers()
    {
        $users = User::all();
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Delete the user's image if it exists
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        // Delete the user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // Fungsi untuk mengirim OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;
        $otp = rand(100000, 999999);

        // Simpan OTP di cache selama 5 menit
        Cache::put('otp_' . $email, $otp, now()->addMinutes(5));

        try {
            Mail::to($email)->send(new OtpMail($email, $otp));
            return response()->json([
                'success' => true,
                'message' => 'OTP berhasil dikirim'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $e->getMessage()
            ], 500);
        }
    }

     // Fungsi untuk forgot password
    // Mengirim OTP untuk reset password
public function forgotPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    $email = $request->email;
    $otp = rand(100000, 999999);

    // Simpan OTP di cache selama 5 menit
    Cache::put('forgot_password_otp_' . $email, $otp, now()->addMinutes(5));

    try {
        Mail::to($email)->send(new OtpMail($email, $otp));
        return response()->json([
            'status' => true,
            'message' => 'OTP untuk reset password telah dikirim'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Gagal mengirim OTP: ' . $e->getMessage()
        ], 500);
    }
}


   // Verifikasi OTP
public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|string|digits:6'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    // Verifikasi OTP
    $storedOtp = Cache::get('forgot_password_otp_' . $request->email);
    if (!$storedOtp || $storedOtp != $request->otp) {
        return response()->json([
            'status' => false,
            'message' => 'OTP tidak valid atau sudah kadaluarsa'
        ], 400);
    }

    // Jika OTP benar, tandai bahwa verifikasi berhasil
    Cache::put('verified_email_' . $request->email, true, now()->addMinutes(10));

    return response()->json([
        'status' => true,
        'message' => 'OTP berhasil diverifikasi'
    ]);
}
// Reset Password
public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'new_password' => 'required|string|min:8'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

    // Periksa apakah email telah diverifikasi sebelumnya
    $isVerified = Cache::get('verified_email_' . $request->email);
    if (!$isVerified) {
        return response()->json([
            'status' => false,
            'message' => 'Email belum diverifikasi dengan OTP'
        ], 403);
    }

    // Update password pengguna
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->new_password);
    $user->save();

    // Hapus cache verifikasi
    Cache::forget('forgot_password_otp_' . $request->email);
    Cache::forget('verified_email_' . $request->email);

    return response()->json([
        'status' => true,
        'message' => 'Password berhasil direset'
    ]);
}

}
