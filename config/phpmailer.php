<?php
return [
    'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
    'smtp_auth' => true,
    'username' => env('MAIL_USERNAME', 'mochfremas67@gmail.com'), // Ganti dengan email Anda
    'password' => env('MAIL_PASSWORD', 'FrekantinPassword123'),    // Ganti dengan App Password Gmail
    'smtp_secure' => env('MAIL_ENCRYPTION', 'tls'),
    'port' => env('MAIL_PORT', 587),
    'from_email' => env('MAIL_FROM_ADDRESS', 'your-email@gmail.com'),
    'from_name' => env('MAIL_FROM_NAME', 'Your App Name'),
];
