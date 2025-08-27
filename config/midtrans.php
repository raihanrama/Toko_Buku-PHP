<?php
// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Midtrans Configuration
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-XXXXXXXXXXXXXXXX'); // Ganti dengan Server Key Anda
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-XXXXXXXXXXXXXXXX'); // Ganti dengan Client Key Anda
define('MIDTRANS_IS_PRODUCTION', false); // Set to true for production

// Midtrans API URLs
define('MIDTRANS_SANDBOX_URL', 'https://api.sandbox.midtrans.com');
define('MIDTRANS_PRODUCTION_URL', 'https://api.midtrans.com');

// Get base URL based on environment
function getMidtransBaseUrl() {
    return MIDTRANS_IS_PRODUCTION ? MIDTRANS_PRODUCTION_URL : MIDTRANS_SANDBOX_URL;
}

// Initialize Midtrans configuration
\Midtrans\Config::$serverKey = MIDTRANS_SERVER_KEY;
\Midtrans\Config::$isProduction = MIDTRANS_IS_PRODUCTION;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Midtrans API Headers
function getMidtransHeaders() {
    return [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ];
}

// Create Midtrans Transaction
function createMidtransTransaction($orderId, $amount, $customerDetails) {
    $params = [
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => $amount
        ],
        'customer_details' => $customerDetails,
        'item_details' => [],
        'expiry' => [
            'start_time' => date('Y-m-d H:i:s O'),
            'unit' => 'day',
            'duration' => 1
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, getMidtransBaseUrl() . '/v1/transactions');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, getMidtransHeaders());
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
} 