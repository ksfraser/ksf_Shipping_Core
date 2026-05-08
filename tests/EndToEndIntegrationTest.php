<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\CanadianShippingCalculator;
use Ksfraser\Shipping\Carrier\CanadaPostAdapter;
use Ksfraser\Shipping\Carrier\UPS_CanadaAdapter;

/**
 * End-to-End Integration Test
 * 
 * Tests the full flow from calculator → carrier → response
 */
class EndToEndIntegrationTest extends TestCase {
    
    public function testCalculatorWithCanadaPost(): void {
        // Skip if no credentials
        $apiKey = getenv('CP_API_KEY') ?: ($_ENV['CP_API_KEY'] ?? '');
        $customerNumber = getenv('CP_CUSTOMER_NUMBER') ?: ($_ENV['CP_CUSTOMER_NUMBER'] ?? '');
        
        if (empty($apiKey) || empty($customerNumber)) {
            $this->markTestSkipped('No Canada Post credentials for end-to-end test.');
        }
        
        $calculator = new CanadianShippingCalculator();
        
        $calculator->setStoreAddress([
            'company' => 'Test Store',
            'address' => '123 Main St',
            'city' => 'Ottawa',
            'state' => 'ON',
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ]);
        
        $calculator->registerCarrier(new CanadaPostAdapter([
            'api_key' => $apiKey,
            'customer_number' => $customerNumber,
            'test_mode' => false
        ]));
        
        $customerAddress = [
            'company' => 'Customer',
            'address' => '456 Yonge St',
            'city' => 'Toronto',
            'state' => 'ON',
            'post_code' => 'M5B 2H1',
            'country' => 'CA'
        ];
        
        $parcel = [
            'weight' => 2.0,
            'length' => 30,
            'width' => 20,
            'height' => 15
        ];
        
        $quotes = $calculator->getQuotes($customerAddress, $parcel);
        
        $this->assertIsArray($quotes);
        $this->assertArrayHasKey('Canada Post', $quotes);
        $this->assertNotEmpty($quotes['Canada Post']['rates']);
        
        echo "\nEnd-to-End Quotes from Canada Post:\n";
        foreach ($quotes['Canada Post']['rates'] as $rate) {
            echo sprintf("  %s: $%.2f %s\n", 
                $rate['service_name'], 
                $rate['rate'], 
                $rate['currency']
            );
        }
    }
    
    public function testGetCheapestQuote(): void {
        $apiKey = getenv('CP_API_KEY') ?: ($_ENV['CP_API_KEY'] ?? '');
        $customerNumber = getenv('CP_CUSTOMER_NUMBER') ?: ($_ENV['CP_CUSTOMER_NUMBER'] ?? '');
        
        if (empty($apiKey) || empty($customerNumber)) {
            $this->markTestSkipped('No Canada Post credentials for end-to-end test.');
        }
        
        $calculator = new CanadianShippingCalculator();
        $calculator->setStoreAddress([
            'post_code' => 'K1A 0A1', 'country' => 'CA'
        ]);
        $calculator->registerCarrier(new CanadaPostAdapter([
            'api_key' => $apiKey,
            'customer_number' => $customerNumber
        ]));
        
        $cheapest = $calculator->getCheapestQuote(
            ['post_code' => 'M5B 2H1', 'country' => 'CA'],
            ['weight' => 1.0, 'length' => 10, 'width' => 10, 'height' => 10]
        );
        
        $this->assertNotNull($cheapest);
        $this->assertArrayHasKey('rate', $cheapest);
        $this->assertGreaterThan(0, $cheapest['rate']);
        
        echo sprintf("\nCheapest Quote: %s - $%.2f\n", 
            $cheapest['service_name'], 
            $cheapest['rate']
        );
    }
}
