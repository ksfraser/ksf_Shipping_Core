<?php
require_once 'vendor/autoload.php';

use Ksfraser\Shipping\Carrier\CanadaPostAdapter;

// Test with sandbox or public endpoint
$adapter = new CanadaPostAdapter([
    'api_key' => 'test',
    'customer_number' => '123456',
    'test_mode' => false
]);

echo "Testing Canada Post API...\n";

$from = ['post_code' => 'K1A0A1', 'country' => 'CA'];
$to = ['post_code' => 'M5B2H1', 'country' => 'CA'];
$parcel = ['weight' => 1.5, 'length' => 30, 'width' => 20, 'height' => 10];

$rates = $adapter->getRates($from, $to, $parcel);

if (empty($rates)) {
    echo "No rates returned (likely auth error as expected with test credentials)\n";
    echo "If you have real credentials, set them in the script.\n";
} else {
    echo "SUCCESS! Got rates:\n";
    foreach ($rates as $rate) {
        echo "  {$rate['service_name']}: \${$rate['rate']} {$rate['currency']}\n";
    }
}

echo "\nConfig valid: " . ($adapter->validateConfig() ? 'YES' : 'NO') . "\n";
