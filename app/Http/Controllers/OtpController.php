<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\EmailService;
use Illuminate\Support\Facades\Session;

class OtpController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $otp = rand(100000, 999999); // Generate OTP 6 digit
        $recipientEmail = $request->input('email');

        // Simpan OTP di session (atau database jika diperlukan)
        Session::put('otp', $otp);
        Session::put('otp_expiration', now()->addMinutes(10)); // OTP berlaku 10 menit

        // Kirim email OTP
        if ($this->emailService->sendOtpEmail($recipientEmail, $otp)) {
            return response()->json(['status' => true, 'message' => 'OTP sent successfully']);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to send OTP'], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        $enteredOtp = $request->input('otp');
        $storedOtp = Session::get('otp');
        $expiration = Session::get('otp_expiration');

        if ($storedOtp && now()->lessThanOrEqualTo($expiration) && $enteredOtp == $storedOtp) {
            return response()->json(['status' => true, 'message' => 'OTP verified successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Invalid or expired OTP'], 400);
    }
}
