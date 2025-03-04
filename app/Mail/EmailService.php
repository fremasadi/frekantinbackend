<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    protected $config;

    public function __construct()
    {
        // Load konfigurasi dari config/phpmailer.php
        $this->config = config('phpmailer');
    }

    public function sendOtpEmail($recipientEmail, $otp)
    {
        $mail = new PHPMailer(true);

        try {
            // Konfigurasi server email
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = $this->config['smtp_auth'];
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['smtp_secure'];
            $mail->Port = $this->config['port'];

            // Pengaturan email pengirim dan penerima
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($recipientEmail);

            // Konten email
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Hello, your OTP code is: <b>$otp</b>. It will expire in 10 minutes.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false; // Gagal
        }
    }
}
