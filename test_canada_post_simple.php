<?php
require_once 'vendor/autoload.php';

use Ksfraser\Shipping\Carrier\CanadaPostAdapter;

// Try without real credentials - some APIs allow public access for basic quotes
$adapter = new CanadaPostAdapter([
    'api_key' => '',  // Empty = test
    'customer_number' => '',
    'test_mode' => false
]);

echo "Testing Canada Post API endpoint...\n";

$from = ['post_code' => 'K1A0A1', 'country' => 'CA'];
$to = ['post_code' => 'M5B2H1', 'country' => 'CA'];
$parcel = ['weight' => 1.5, 'length' => 30, 'width' => 20, 'height' => 10];

// Test if endpoint is reachable
$ch = curl_init('https://soa-gw.canadapost.ca/rs/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . substr($response, 0, 200) . "\n";

echo "\nCanada Post API requires authentication for rate quotes.\n";
echo "Get credentials at: https://developer.canadapost.ca/\n";
