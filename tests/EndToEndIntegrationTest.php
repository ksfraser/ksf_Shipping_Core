<?php
namespace Ksfraser\Shipping\Tests;

use PHPUnit\Framework\TestCase;
use Ksfraser\Shipping\CanadianShippingCalculator;
use Ksfraser\Shipping\Carrier\FlatRateMethod;
use Ksfraser\Shipping\Carrier\FreeShippingMethod;

/**
 * End-to-End Integration Test
 * 
 * Tests the full flow from calculator → carrier → response
 */
class EndToEndIntegrationTest extends TestCase {
    
    public function testCalculatorWithFlatRate(): void {
        $calculator = new CanadianShippingCalculator();
        
        $calculator->setStoreAddress([
            'company' => 'Test Store',
            'address' => '123 Main St',
            'city' => 'Ottawa',
            'state' => 'ON',
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ]);
        
        $calculator->registerCarrier(new FlatRateMethod([
            'rate' => 9.99,
            'name' => 'Flat Rate Shipping'
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
        $this->assertArrayHasKey('Flat Rate Shipping', $quotes);
        $this->assertNotEmpty($quotes['Flat Rate Shipping']['rates']);
    }
    
    public function testCalculatorWithFreeShipping(): void {
        $calculator = new CanadianShippingCalculator();
        
        $calculator->setStoreAddress([
            'company' => 'Test Store',
            'post_code' => 'K1A 0A1',
            'country' => 'CA'
        ]);
        
        $calculator->registerCarrier(new FreeShippingMethod([
            'min_order_amount' => 50.00,
            'name' => 'Free Shipping'
        ]));
        
        $customerAddress = [
            'post_code' => 'M5B 2H1',
            'country' => 'CA'
        ];
        
        $parcel = [
            'weight' => 1.0,
            'length' => 10,
            'width' => 10,
            'height' => 10
        ];
        
        $quotes = $calculator->getQuotes($customerAddress, $parcel, ['order_amount' => 75.00]);
        
        $this->assertIsArray($quotes);
        $this->assertArrayHasKey('Free Shipping', $quotes);
    }
    
    public function testGetCheapestQuote(): void {
        $calculator = new CanadianShippingCalculator();
        
        $calculator->registerCarrier(new FlatRateMethod([
            'rate' => 9.99,
            'name' => 'Standard'
        ]));
        
        $calculator->registerCarrier(new FlatRateMethod([
            'rate' => 19.99,
            'name' => 'Express'
        ]));
        
        $cheapest = $calculator->getCheapestQuote(
            ['post_code' => 'M5B 2H1', 'country' => 'CA'],
            ['weight' => 1.0, 'length' => 10, 'width' => 10, 'height' => 10]
        );
        
        $this->assertNotNull($cheapest);
        $this->assertArrayHasKey('rate', $cheapest);
        $this->assertEquals(9.99, $cheapest['rate']);
    }
    
    public function testCalculatorWithMultipleCarriers(): void {
        $calculator = new CanadianShippingCalculator();
        
        $calculator->registerCarrier(new FlatRateMethod([
            'rate' => 9.99,
            'name' => 'Standard'
        ]));
        
        $calculator->registerCarrier(new FreeShippingMethod([
            'min_order_amount' => 100.00,
            'name' => 'Free Over $100'
        ]));
        
        $options = ['order_amount' => 150.00];
        $quotes = $calculator->getQuotes(
            ['post_code' => 'M5B 2H1', 'country' => 'CA'],
            ['weight' => 1.0, 'length' => 10, 'width' => 10, 'height' => 10],
            $options
        );
        
        $this->assertArrayHasKey('Standard', $quotes);
        $this->assertArrayHasKey('Free Over $100', $quotes);
    }
}