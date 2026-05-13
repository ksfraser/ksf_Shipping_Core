# Shipping Calculation Core - Test Plan

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Document Type** | Test Plan |
| **Version** | 1.0.0 |

---

## 1. Test Objectives

| Objective | Description |
|-----------|-------------|
| **Functional Verification** | Verify all shipping methods calculate correct rates |
| **Carrier Integration** | Verify carrier adapters return accurate quotes |
| **Edge Case Handling** | Verify graceful handling of invalid inputs and errors |
| **Performance** | Verify response times meet performance targets |
| **Regression Prevention** | Prevent shipping calculation bugs in future releases |

---

## 2. Test Scope

### 2.1 In Scope

- ShippingCalculator class
- CanadianShippingCalculator class
- ShippingMethodInterface implementations (FlatRateMethod, FreeShippingMethod)
- CarrierAdapterInterface implementations (CanadaPostAdapter)
- Destination matching logic
- Rate sorting functionality

### 2.2 Out of Scope

- Platform-specific UI components
- Database integration (tested separately)
- Real carrier API endpoints (use mocks)
- Tax calculation (configuration-dependent)

---

## 3. Test Environment

### 3.1 Test Configuration

| Component | Specification |
|-----------|---------------|
| PHP Version | 7.3+ |
| PHPUnit | 9.x |
| Mock Library | PHPUnit built-in mocks |
| Test Database | SQLite in-memory |

### 3.2 Dependencies

```json
{
    "require-dev": {
        "phpunit/phpunit": "^9.0"
    }
}
```

---

## 4. Test Scenarios

### 4.1 ShippingCalculator Tests

#### SHIP-TEST-001: Register and Retrieve Methods

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-001 |
| **Category** | Core Functionality |
| **Priority** | Critical |

```php
public function testRegisterMethod(): void
{
    $calculator = new ShippingCalculator();
    $method = new FlatRateMethod(['cost' => 10.00]);
    
    $calculator->registerMethod($method);
    
    $rates = $calculator->calculateRates([
        'destination' => ['country' => 'CA']
    ]);
    
    $this->assertCount(1, $rates);
    $this->assertEquals(10.00, $rates[0]['cost']);
}
```

**Pass Criteria:** Method is registered and calculated

---

#### SHIP-TEST-002: Calculate Rates with Multiple Methods

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-002 |
| **Category** | Core Functionality |
| **Priority** | Critical |

```php
public function testCalculateRatesWithMultipleMethods(): void
{
    $calculator = new ShippingCalculator();
    $calculator->registerMethod(new FlatRateMethod(['cost' => 15.00]));
    $calculator->registerMethod(new FreeShippingMethod(['min_amount' => 100]));
    
    $calculator->addPackage([
        'weight' => 1.0,
        'dimensions' => ['length' => 10, 'width' => 10, 'height' => 10]
    ]);
    
    $rates = $calculator->calculateRates([
        'destination' => ['country' => 'CA'],
        'cart_total' => 150.00
    ]);
    
    $this->assertCount(2, $rates);
    $this->assertEquals(0, $rates[0]['cost']); // Free shipping first
    $this->assertEquals(15.00, $rates[1]['cost']); // Flat rate second
}
```

**Pass Criteria:** Both methods return rates, sorted by cost

---

#### SHIP-TEST-003: Skip Disabled Methods

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-003 |
| **Category** | Core Functionality |
| **Priority** | High |

```php
public function testSkipDisabledMethods(): void
{
    $calculator = new ShippingCalculator();
    $calculator->registerMethod(new FlatRateMethod(['cost' => 10.00, 'enabled' => false]));
    $calculator->registerMethod(new FlatRateMethod(['cost' => 15.00, 'enabled' => true]));
    
    $rates = $calculator->calculateRates([
        'destination' => ['country' => 'CA']
    ]);
    
    $this->assertCount(1, $rates);
    $this->assertEquals(15.00, $rates[0]['cost']);
}
```

**Pass Criteria:** Disabled methods are skipped

---

#### SHIP-TEST-004: Get Cheapest Rate

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-004 |
| **Category** | Core Functionality |
| **Priority** | High |

```php
public function testGetCheapestRate(): void
{
    $calculator = new ShippingCalculator();
    $calculator->registerMethod(new FlatRateMethod(['cost' => 25.00]));
    $calculator->registerMethod(new FlatRateMethod(['cost' => 10.00]));
    $calculator->registerMethod(new FlatRateMethod(['cost' => 15.00]));
    
    $cheapest = $calculator->getCheapestRate([
        'destination' => ['country' => 'CA']
    ]);
    
    $this->assertNotNull($cheapest);
    $this->assertEquals(10.00, $cheapest['cost']);
}
```

**Pass Criteria:** Cheapest rate returned

---

### 4.2 Flat Rate Method Tests

#### SHIP-TEST-010: Flat Rate Basic Calculation

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-010 |
| **Category** | Flat Rate Method |
| **Priority** | Critical |

```php
public function testFlatRateCalculation(): void
{
    $method = new FlatRateMethod(['cost' => 9.99]);
    
    $rates = $method->calculateShipping(
        [['weight' => 1.0]],
        ['destination' => ['country' => 'CA']]
    );
    
    $this->assertCount(1, $rates);
    $this->assertEquals('Flat Rate', $rates[0]['title']);
    $this->assertEquals(9.99, $rates[0]['cost']);
}
```

**Pass Criteria:** Flat rate returns correct cost

---

#### SHIP-TEST-011: Flat Rate Multi-Package

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-011 |
| **Category** | Flat Rate Method |
| **Priority** | High |

```php
public function testFlatRateMultiPackage(): void
{
    $method = new FlatRateMethod(['cost' => 5.00]);
    
    $rates = $method->calculateShipping(
        [
            ['weight' => 1.0],
            ['weight' => 2.0]
        ],
        ['destination' => ['country' => 'CA']]
    );
    
    $this->assertEquals(10.00, $rates[0]['cost']); // 2 x $5
}
```

**Pass Criteria:** Cost multiplied by package count

---

#### SHIP-TEST-012: Flat Rate Destination Restriction - Country

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-012 |
| **Category** | Flat Rate Method |
| **Priority** | High |

```php
public function testFlatRateCountryRestriction(): void
{
    $method = new FlatRateMethod([
        'cost' => 10.00,
        'destinations' => [['country' => 'CA']]
    ]);
    
    $this->assertTrue($method->isAvailableForDestination(['country' => 'CA']));
    $this->assertFalse($method->isAvailableForDestination(['country' => 'US']));
}
```

**Pass Criteria:** Country restriction enforced

---

#### SHIP-TEST-013: Flat Rate Destination Restriction - Postcode Pattern

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-013 |
| **Category** | Flat Rate Method |
| **Priority** | Medium |

```php
public function testFlatRatePostcodePattern(): void
{
    $method = new FlatRateMethod([
        'cost' => 10.00,
        'destinations' => [['postcode' => 'M*']]
    ]);
    
    $this->assertTrue($method->isAvailableForDestination(['postcode' => 'M5V 2T6']));
    $this->assertFalse($method->isAvailableForDestination(['postcode' => 'L5V 1A1']));
}
```

**Pass Criteria:** Wildcard postcode matching works

---

### 4.3 Free Shipping Method Tests

#### SHIP-TEST-020: Free Shipping Eligible

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-020 |
| **Category** | Free Shipping Method |
| **Priority** | Critical |

```php
public function testFreeShippingEligible(): void
{
    $method = new FreeShippingMethod(['min_amount' => 100]);
    
    $rates = $method->calculateShipping(
        [],
        ['cart_total' => 150.00]
    );
    
    $this->assertCount(1, $rates);
    $this->assertEquals(0, $rates[0]['cost']);
}
```

**Pass Criteria:** $0 rate when eligible

---

#### SHIP-TEST-021: Free Shipping Not Eligible

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-021 |
| **Category** | Free Shipping Method |
| **Priority** | Critical |

```php
public function testFreeShippingNotEligible(): void
{
    $method = new FreeShippingMethod(['min_amount' => 100]);
    
    $rates = $method->calculateShipping(
        [],
        ['cart_total' => 50.00]
    );
    
    $this->assertEmpty($rates);
}
```

**Pass Criteria:** No rate when below threshold

---

### 4.4 Canadian Carrier Tests

#### SHIP-TEST-030: Carrier Quote Collection

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-030 |
| **Category** | Carrier Integration |
| **Priority** | Critical |

```php
public function testGetQuotesFromCarriers(): void
{
    $calculator = new CanadianShippingCalculator();
    
    $mockCarrier = $this->createMock(CarrierAdapterInterface::class);
    $mockCarrier->method('getName')->willReturn('Test Carrier');
    $mockCarrier->method('validateConfig')->willReturn(true);
    $mockCarrier->method('supportsGuestQuotes')->willReturn(true);
    $mockCarrier->method('getRates')->willReturn([
        ['service_code' => 'STD', 'service_name' => 'Standard', 'rate' => 10.00, 'currency' => 'CAD']
    ]);
    
    $calculator->registerCarrier($mockCarrier);
    
    $quotes = $calculator->getQuotes(
        ['post_code' => 'M5V 2T6', 'country' => 'CA'],
        ['weight' => 1.0, 'length' => 10, 'width' => 10, 'height' => 10]
    );
    
    $this->assertArrayHasKey('Test Carrier', $quotes);
    $this->assertCount(1, $quotes['Test Carrier']['rates']);
}
```

**Pass Criteria:** Carrier quotes collected and grouped

---

#### SHIP-TEST-031: Skip Invalid Carrier Config

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-031 |
| **Category** | Carrier Integration |
| **Priority** | High |

```php
public function testSkipInvalidCarrierConfig(): void
{
    $calculator = new CanadianShippingCalculator();
    
    $validCarrier = $this->createMock(CarrierAdapterInterface::class);
    $validCarrier->method('getName')->willReturn('Valid Carrier');
    $validCarrier->method('validateConfig')->willReturn(true);
    $validCarrier->method('getRates')->willReturn([['rate' => 10.00]]);
    
    $invalidCarrier = $this->createMock(CarrierAdapterInterface::class);
    $invalidCarrier->method('getName')->willReturn('Invalid Carrier');
    $invalidCarrier->method('validateConfig')->willReturn(false);
    
    $calculator->registerCarrier($validCarrier);
    $calculator->registerCarrier($invalidCarrier);
    
    $quotes = $calculator->getQuotes(['post_code' => 'M5V 2T6'], ['weight' => 1.0]);
    
    $this->assertArrayHasKey('Valid Carrier', $quotes);
    $this->assertArrayNotHasKey('Invalid Carrier', $quotes);
}
```

**Pass Criteria:** Invalid configurations skipped

---

#### SHIP-TEST-032: Get Cheapest Quote

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-032 |
| **Category** | Carrier Integration |
| **Priority** | High |

```php
public function testGetCheapestQuote(): void
{
    $calculator = new CanadianShippingCalculator();
    
    $carrier1 = $this->createMock(CarrierAdapterInterface::class);
    $carrier1->method('getName')->willReturn('Carrier 1');
    $carrier1->method('validateConfig')->willReturn(true);
    $carrier1->method('getRates')->willReturn([
        ['service_code' => 'EXP', 'rate' => 15.00]
    ]);
    
    $carrier2 = $this->createMock(CarrierAdapterInterface::class);
    $carrier2->method('getName')->willReturn('Carrier 2');
    $carrier2->method('validateConfig')->willReturn(true);
    $carrier2->method('getRates')->willReturn([
        ['service_code' => 'STD', 'rate' => 10.00]
    ]);
    
    $calculator->registerCarrier($carrier1);
    $calculator->registerCarrier($carrier2);
    
    $cheapest = $calculator->getCheapestQuote(
        ['post_code' => 'M5V 2T6'],
        ['weight' => 1.0]
    );
    
    $this->assertEquals(10.00, $cheapest['rate']);
    $this->assertEquals('Carrier 2', $cheapest['carrier']);
}
```

**Pass Criteria:** Cheapest quote across carriers returned

---

### 4.5 Edge Case Tests

#### SHIP-TEST-040: Empty Rates When No Methods

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-040 |
| **Category** | Edge Cases |
| **Priority** | High |

```php
public function testEmptyRatesWithNoMethods(): void
{
    $calculator = new ShippingCalculator();
    
    $rates = $calculator->calculateRates([
        'destination' => ['country' => 'CA']
    ]);
    
    $this->assertEmpty($rates);
}
```

**Pass Criteria:** Empty array returned

---

#### SHIP-TEST-041: Null Cheapest Rate When No Methods

| Field | Value |
|-------|-------|
| **Test ID** | SHIP-TEST-041 |
| **Category** | Edge Cases |
| **Priority** | Medium |

```php
public function testNullCheapestRateWithNoMethods(): void
{
    $calculator = new ShippingCalculator();
    
    $cheapest = $calculator->getCheapestRate([
        'destination' => ['country' => 'CA']
    ]);
    
    $this->assertNull($cheapest);
}
```

**Pass Criteria:** Null returned when no rates

---

## 5. Test Data

### 5.1 Standard Test Data

```php
$torontoAddress = [
    'country' => 'CA',
    'state' => 'ON',
    'postcode' => 'M5V 2T6',
    'city' => 'Toronto'
];

$vancouverAddress = [
    'country' => 'CA',
    'state' => 'BC',
    'postcode' => 'V6B 1A1',
    'city' => 'Vancouver'
];

$standardParcel = [
    'weight' => 2.5,
    'length' => 30,
    'width' => 20,
    'height' => 15
];

$heavyParcel = [
    'weight' => 25.0,
    'length' => 60,
    'width' => 40,
    'height' => 30
];
```

### 5.2 Boundary Test Data

| Parameter | Min | Max | Edge Values |
|-----------|-----|-----|-------------|
| Weight (kg) | 0.001 | 1000 | 0.001, 1000, 0 |
| Dimensions (cm) | 1 | 10000 | 1, 10000, 0 |
| Flat Rate Cost | 0 | 10000 | 0, 10000, -1 |
| Min Order Amount | 0 | 100000 | 0, 100000 |

---

## 6. Pass Criteria Summary

| Test Category | Pass Criteria |
|--------------|---------------|
| Core Functionality | All rate calculations return correct values |
| Method Registration | Methods are correctly registered and retrieved |
| Rate Sorting | Rates sorted by cost ascending |
| Destination Matching | Restrictions correctly enforced |
| Free Shipping | Eligibility correctly determined |
| Carrier Integration | Quotes collected from all valid carriers |
| Error Handling | Invalid configs skipped, exceptions thrown appropriately |
| Edge Cases | Empty inputs handled gracefully |

---

## 7. Coverage Targets

| Component | Target Coverage |
|-----------|-----------------|
| ShippingCalculator | 100% |
| CanadianShippingCalculator | 95% |
| FlatRateMethod | 100% |
| FreeShippingMethod | 100% |
| CarrierAdapterInterface | 100% |
| CanadaPostAdapter | 90% |

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*