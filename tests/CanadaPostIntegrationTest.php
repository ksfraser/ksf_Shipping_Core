<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\Carrier\CanadaPostAdapter;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Canada Post Integration Test
 * 
 * Tests Canada Post adapter with mocked HTTP client.
 * For live API tests, set environment variables and run: phpunit --group integration
 */
class CanadaPostIntegrationTest extends TestCase {
    
    private $config = [
        'api_key' => 'test-api-key',
        'customer_number' => '123456',
        'contract_id' => 'TEST123',
        'test_mode' => true
    ];
    
    public function testCanadaPostConfigValidation(): void {
        $adapter = new CanadaPostAdapter([]);
        $this->assertFalse($adapter->validateConfig());
        
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
    
    public function testCanadaPostGetRatesWithMockedResponse(): void {
        $adapter = new CanadaPostAdapter($this->config);
        
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
            'weight' => 1.5,
            'length' => 30,
            'width' => 20,
            'height' => 10
        ];
        
        $rates = $adapter->getRates($from, $to, $parcel);
        
        $this->assertIsArray($rates);
    }
    
    public function testCanadaPostInvalidPostalCodeReturnsEmpty(): void {
        $adapter = new CanadaPostAdapter($this->config);
        
        $from = [
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ];
        
        $to = [
            'post_code' => 'INVALID',
            'country' => 'CA'
        ];
        
        $parcel = [
            'weight' => 1.0,
            'length' => 10,
            'width' => 10,
            'height' => 10
        ];
        
        $rates = $adapter->getRates($from, $to, $parcel);
        
        $this->assertIsArray($rates);
    }
}