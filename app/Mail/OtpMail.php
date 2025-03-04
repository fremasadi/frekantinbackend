<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $user;

    public function __construct($user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->html($this->generateEmailContent())
                    ->subject('Kode OTP Anda');
    }

    private function generateEmailContent()
    {
        return "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Kode OTP Anda</h2>
                <p>Halo!</p>
                <p>Berikut adalah kode OTP Anda:</p>
                <div style='background-color: #f4f4f4; padding: 10px; margin: 20px 0; text-align: center;'>
                    <h1 style='color: #333; letter-spacing: 5px;'>{$this->otp}</h1>
                </div>
                <p>Kode OTP ini akan kadaluarsa dalam 5 menit.</p>
                <p>Jika Anda tidak merasa meminta kode OTP ini, abaikan email ini.</p>
                <p>Terima kasih!</p>
            </div>
        ";
    }
}