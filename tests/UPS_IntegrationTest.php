<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\Carrier\UPS_CanadaAdapter;

/**
 * UPS Canada Integration Test
 * 
 * Tests UPS Canada adapter.
 * For live API tests, set environment variables and run: phpunit --group integration
 */
class UPS_IntegrationTest extends TestCase {
    
    private $config = [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'shipper_number' => 'TEST123',
        'test_mode' => true
    ];
    
    public function testUPS_ConfigValidation(): void {
        $adapter = new UPS_CanadaAdapter([]);
        $this->assertFalse($adapter->validateConfig());
        
        $adapter = new UPS_CanadaAdapter([
            'client_id' => 'test-id',
            'client_secret' => 'test-secret'
        ]);
        $this->assertTrue($adapter->validateConfig());
    }
    
    public function testUPS_GetRatesWithMockedResponse(): void {
        $adapter = new UPS_CanadaAdapter($this->config);
        
        $from = [
            'company' => 'Test Store',
            'city' => 'Ottawa',
            'state' => 'ON',
            'post_code' => 'K1A0A1',
            'country' => 'CA'
        ];
        
        $to = [
            'company' => 'Test Customer',
            'city' => 'Toronto',
            'state' => 'ON',
            'post_code' => 'M5B2H1',
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
    
    public function testUPS_SupportsGuestQuotes(): void {
        $adapter = new UPS_CanadaAdapter($this->config);
        $this->assertFalse($adapter->supportsGuestQuotes());
    }
}