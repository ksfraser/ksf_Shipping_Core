<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\ShippingCalculator;
use Ksfraser\Shipping\FlatRateMethod;
use Ksfraser\Shipping\FreeShippingMethod;

class ShippingCalculatorTest extends TestCase {
    
    private $calculator;
    
    protected function setUp(): void {
        $this->calculator = new ShippingCalculator();
    }
    
    public function testRegisterAndCalculateFlatRate(): void {
        $flatRate = new FlatRateMethod([
            'title' => 'Standard Shipping',
            'cost' => 10.00,
            'enabled' => true
        ]);
        
        $this->calculator->registerMethod($flatRate);
        
        $this->calculator->addPackage([
            'weight' => 2.5,
            'dimensions' => ['length' => 30, 'width' => 20, 'height' => 10]
        ]);
        
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA', 'state' => 'ON', 'postcode' => 'K1A 0A1']
        ]);
        
        $this->assertCount(1, $rates);
        $this->assertEquals('Standard Shipping', $rates[0]['title']);
        $this->assertEquals(10.00, $rates[0]['cost']);
    }
    
    public function testFreeShippingThreshold(): void {
        $freeShipping = new FreeShippingMethod([
            'title' => 'Free Shipping',
            'min_amount' => 100,
            'enabled' => true
        ]);
        
        $this->calculator->registerMethod($freeShipping);
        
        // Cart total < threshold - no free shipping
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA']
        ], ['cart_total' => 50]);
        
        $this->assertCount(0, $rates);
        
        // Cart total >= threshold - free shipping available
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA']
        ], ['cart_total' => 150]);
        
        $this->assertCount(1, $rates);
        $this->assertEquals(0, $rates[0]['cost']);
    }
    
    public function testDestinationValidation(): void {
        $flatRate = new FlatRateMethod([
            'title' => 'Ontario Only',
            'cost' => 15.00,
            'enabled' => true,
            'destinations' => [
                ['country' => 'CA', 'state' => 'ON']
            ]
        ]);
        
        $this->calculator->registerMethod($flatRate);
        
        // Valid destination
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA', 'state' => 'ON', 'postcode' => 'K1A 0A1']
        ]);
        $this->assertCount(1, $rates);
        
        // Invalid destination
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA', 'state' => 'BC', 'postcode' => 'V6B 0A1']
        ]);
        $this->assertCount(0, $rates);
    }
    
    public function testPostalCodeWildcard(): void {
        $flatRate = new FlatRateMethod([
            'title' => 'Eastern Canada',
            'cost' => 20.00,
            'enabled' => true,
            'destinations' => [
                ['country' => 'CA', 'postcode' => 'K*'], // Ontario K postcodes
                ['country' => 'CA', 'postcode' => 'M*']  // Toronto M postcodes
            ]
        ]);
        
        $this->calculator->registerMethod($flatRate);
        
        // K1A 0A1 matches K*
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA', 'postcode' => 'K1A 0A1']
        ]);
        $this->assertCount(1, $rates);
        
        // V6B 0A1 does not match
        $rates = $this->calculator->calculateRates([
            'destination' => ['country' => 'CA', 'postcode' => 'V6B 0A1']
        ]);
        $this->assertCount(0, $rates);
    }
    
    public function testCheapestRate(): void {
        $this->calculator->registerMethod(new FlatRateMethod([
            'title' => 'Express', 'cost' => 25.00, 'enabled' => true
        ]));
        $this->calculator->registerMethod(new FlatRateMethod([
            'title' => 'Standard', 'cost' => 10.00, 'enabled' => true
        ]));
        
        $cheapest = $this->calculator->getCheapestRate([]);
        
        $this->assertEquals('Standard', $cheapest['title']);
        $this->assertEquals(10.00, $cheapest['cost']);
    }
    
    public function testDisabledMethodNotReturned(): void {
        $this->calculator->registerMethod(new FlatRateMethod([
            'title' => 'Disabled Method', 'cost' => 10.00, 'enabled' => false
        ]));
        
        $rates = $this->calculator->calculateRates([]);
        
        $this->assertCount(0, $rates);
    }
}
