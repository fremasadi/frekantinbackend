<?php

return [
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions', // URL untuk sandbox
];
