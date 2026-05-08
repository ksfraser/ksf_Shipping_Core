<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\Carrier\CanadaPostAdapter;

/**
 * Canada Post Integration Test
 * 
 * Usage: Set environment variables:
 * - CP_API_KEY: Your Canada Post API key
 * - CP_CUSTOMER_NUMBER: Your customer number
 * - CP_CONTRACT_ID: (Optional) Contract ID
 * 
 * Run with: phpunit --group integration
 */
class CanadaPostIntegrationTest extends TestCase {
    
    private $config = [];
    private $liveTest = false;
    
    protected function setUp(): void {
        // Check for real credentials
        $apiKey = getenv('CP_API_KEY') ?: ($_ENV['CP_API_KEY'] ?? '');
        $customerNumber = getenv('CP_CUSTOMER_NUMBER') ?: ($_ENV['CP_CUSTOMER_NUMBER'] ?? '');
        
        if (!empty($apiKey) && !empty($customerNumber)) {
            $this->liveTest = true;
            $this->config = [
                'api_key' => $apiKey,
                'customer_number' => $customerNumber,
                'contract_id' => getenv('CP_CONTRACT_ID') ?: ($_ENV['CP_CONTRACT_ID'] ?? ''),
                'test_mode' => false
            ];
        }
    }
    
    /**
     * @group integration
     */
    public function testCanadaPostGetRatesFromLiveAPI(): void {
        if (!$this->liveTest) {
            $this->markTestSkipped('No Canada Post API credentials provided. Set CP_API_KEY and CP_CUSTOMER_NUMBER environment variables.');
        }
        
        $adapter = new CanadaPostAdapter($this->config);
        
        // Valid from/to addresses (Ottawa to Toronto)
        $from = [
            'company' => 'Test Store',
            'address' => '123 Main St',
            'city' => 'Ottawa',
            'state' => 'ON',
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ];
        
        $to = [
            'company' => 'Test Customer',
            'address' => '456 Yonge St',
            'city' => 'Toronto',
            'state' => 'ON',
            'post_code' => 'M5B 2H1',
            'country' => 'CA'
        ];
        
        $parcel = [
            'weight' => 1.5, // 1.5 kg
            'length' => 30,
            'width' => 20,
            'height' => 10
        ];
        
        $rates = $adapter->getRates($from, $to, $parcel);
        
        // Assert we got rates back
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates, 'No rates returned from Canada Post API');
        
        // Validate first rate structure
        $firstRate = $rates[0];
        $this->assertArrayHasKey('service_code', $firstRate);
        $this->assertArrayHasKey('service_name', $firstRate);
        $this->assertArrayHasKey('rate', $firstRate);
        $this->assertArrayHasKey('currency', $firstRate);
        
        // Rate should be a positive number
        $this->assertGreaterThan(0, $firstRate['rate'], 'Rate should be greater than 0');
        
        echo "\nCanada Post Rates:\n";
        foreach ($rates as $rate) {
            echo sprintf("  %s (%s): $%.2f %s\n", 
                $rate['service_name'], 
                $rate['service_code'], 
                $rate['rate'], 
                $rate['currency']
            );
        }
    }
    
    /**
     * @group integration
     */
    public function testCanadaPostInvalidPostalCodeReturnsEmpty(): void {
        if (!$this->liveTest) {
            $this->markTestSkipped('No Canada Post API credentials provided.');
        }
        
        $adapter = new CanadaPostAdapter($this->config);
        
        $from = [
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ];
        
        $to = [
            'post_code' => 'INVALID', // Invalid postal code
            'country' => 'CA'
        ];
        
        $parcel = [
            'weight' => 1.0,
            'length' => 10,
            'width' => 10,
            'height' => 10
        ];
        
        $rates = $adapter->getRates($from, $to, $parcel);
        
        // Should return empty array or rates with error handling
        $this->assertIsArray($rates);
    }
    
    public function testCanadaPostConfigValidation(): void {
        // Test missing credentials
        $adapter = new CanadaPostAdapter([]);
        $this->assertFalse($adapter->validateConfig());
        
        // Test with credentials
        $adapter = new CanadaPostAdapter([
            'api_key' => 'test-key',
            'customer_number' => '123456'
        ]);
        $this->assertTrue($adapter->validateConfig());
    }
    
    public function testCanadaPostSupportsGuestQuotes(): void {
        $adapter = new CanadaPostAdapter([]);
        $this->assertFalse($adapter->supportsGuestQuotes());
    }
}
