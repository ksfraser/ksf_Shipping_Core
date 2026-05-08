<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\Carrier\UPS_CanadaAdapter;

/**
 * UPS Canada Integration Test
 * 
 * Usage: Set environment variables:
 * - UPS_CLIENT_ID: Your UPS OAuth Client ID
 * - UPS_CLIENT_SECRET: Your UPS OAuth Client Secret
 * - UPS_SHIPPER_NUMBER: Your shipper number
 */
class UPS_IntegrationTest extends TestCase {
    
    private $config = [];
    private $liveTest = false;
    
    protected function setUp(): void {
        $clientId = getenv('UPS_CLIENT_ID') ?: ($_ENV['UPS_CLIENT_ID'] ?? '');
        $clientSecret = getenv('UPS_CLIENT_SECRET') ?: ($_ENV['UPS_CLIENT_SECRET'] ?? '');
        
        if (!empty($clientId) && !empty($clientSecret)) {
            $this->liveTest = true;
            $this->config = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'shipper_number' => getenv('UPS_SHIPPER_NUMBER') ?: ($_ENV['UPS_SHIPPER_NUMBER'] ?? ''),
                'test_mode' => true // Use sandbox
            ];
        }
    }
    
    /**
     * @group integration
     */
    public function testUPS_GetRatesFromAPI(): void {
        if (!$this->liveTest) {
            $this->markTestSkipped('No UPS API credentials provided. Set UPS_CLIENT_ID and UPS_CLIENT_SECRET environment variables.');
        }
        
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
        
        if (!empty($rates)) {
            $firstRate = $rates[0];
            $this->assertArrayHasKey('service_code', $firstRate);
            $this->assertArrayHasKey('rate', $firstRate);
            $this->assertGreaterThan(0, $firstRate['rate']);
            
            echo "\nUPS Rates:\n";
            foreach ($rates as $rate) {
                echo sprintf("  %s (%s): $%.2f %s\n", 
                    $rate['service_name'], 
                    $rate['service_code'], 
                    $rate['rate'], 
                    $rate['currency']
                );
            }
        }
    }
    
    public function testUPS_ConfigValidation(): void {
        $adapter = new UPS_CanadaAdapter([]);
        $this->assertFalse($adapter->validateConfig());
        
        $adapter = new UPS_CanadaAdapter([
            'client_id' => 'test-id',
            'client_secret' => 'test-secret'
        ]);
        $this->assertTrue($adapter->validateConfig());
    }
}
