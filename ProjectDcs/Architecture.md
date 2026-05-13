# Shipping Calculation Core - Architecture

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Module Type** | Business Logic / Framework-Agnostic Core |
| **Version** | 1.0.0 |

---

## 1. Technical Architecture

### 1.1 Architecture Pattern

The module follows the **Business Logic + Platform Adapters** pattern defined in AGENTS.md:

```
┌──────────────────────────────────────────────────────────────────────┐
│                    ksf_Shipping_Core                                 │
│                   (Business Logic Layer)                            │
├──────────────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐      │
│  │ ShippingCalculator│  │CanadianShipping │  │  Methods.php    │      │
│  │                  │  │  Calculator     │  │  - FlatRate     │      │
│  │ - registerMethod │  │                 │  │  - FreeShipping│      │
│  │ - calculateRates│  │ - registerCarrier│  │                 │      │
│  │ - getCheapestRate│  │ - getQuotes     │  │                 │      │
│  └──────────────────┘  │ - getCheapestQuote│  └──────────────────┘      │
│                        └──────────────────┘                             │
├──────────────────────────────────────────────────────────────────────┤
│                         Carrier/                                      │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐    │
│  │CanadaPost    │ │FedEx         │ │UPS           │ │Purolator     │    │
│  │Adapter      │ │CanadaAdapter │ │CanadaAdapter │ │Adapter      │    │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘    │
│  ┌──────────────┐ ┌──────────────┐                                      │
│  │DHL_Canada    │ │Canpar        │                                      │
│  │Adapter       │ │Adapter      │                                      │
│  └──────────────┘ └──────────────┘                                      │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────────────┐
        │           Platform Adapters                 │
        │  (ksf_WooCommerce_Shipping, ksf_FA_Shipping)│
        │  - UI integration                          │
        │  - Database persistence                    │
        │  - Tax calculation                         │
        └─────────────────────────────────────────────┘
```

### 1.2 Directory Structure

```
ksf_Shipping_Core/
├── src/Ksfraser/Shipping/
│   ├── ShippingCalculator.php        # Core calculator
│   ├── CanadianShippingCalculator.php # Carrier integration
│   ├── Methods.php                   # Built-in methods
│   └── Carrier/
│       ├── CarrierAdapterInterface.php # Carrier contract
│       ├── CanadaPostAdapter.php      # Canada Post impl
│       ├── FedEx_CanadaAdapter.php     # FedEx impl
│       ├── UPS_CanadaAdapter.php       # UPS impl
│       ├── PurolatorAdapter.php        # Purolator impl
│       ├── DHL_CanadaAdapter.php       # DHL impl
│       └── CanparAdapter.php           # Canpar impl
├── tests/
│   ├── Unit/
│   │   ├── ShippingCalculatorTest.php
│   │   ├── CanadianShippingCalculatorTest.php
│   │   └── Carrier/
│   │       └── CanadaPostAdapterTest.php
│   └── fixtures/
├── ProjectDcs/
└── composer.json
```

### 1.3 Namespace Structure

```php
namespace Ksfraser\Shipping;           // Core classes
namespace Ksfraser\Shipping\Carrier;    // Carrier adapters
```

---

## 2. Class Diagrams

### 2.1 Core Classes

```
┌─────────────────────────────────────────────────────────────────┐
│                    ShippingCalculator                            │
├─────────────────────────────────────────────────────────────────┤
│ - methods: ShippingMethodInterface[]                            │
│ - packages: array[]                                              │
├─────────────────────────────────────────────────────────────────┤
│ + registerMethod(ShippingMethodInterface): void                  │
│ + addPackage(array): void                                        │
│ + calculateRates(array context): array[]                         │
│ + getCheapestRate(array context): ?array                        │
│ + clearPackages(): void                                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ uses
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              <<interface>> ShippingMethodInterface               │
├─────────────────────────────────────────────────────────────────┤
│ + getId(): string                                                │
│ + getTitle(): string                                             │
│ + isEnabled(): bool                                              │
│ + isAvailableForDestination(array): bool                         │
│ + calculateShipping(array packages, array context): array[]     │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ implements
        ┌─────────────────────┴─────────────────────┐
        │                                       │
┌───────┴───────┐                    ┌───────────┴───────────┐
│ FlatRateMethod│                    │ FreeShippingMethod    │
├───────────────┤                    ├───────────────────────┤
│ - id          │                    │ - id                 │
│ - title       │                    │ - title               │
│ - cost        │                    │ - minAmount           │
│ - taxable     │                    │ - enabled             │
│ - destinations│                    │                       │
├───────────────┤                    ├───────────────────────┤
│ + calculateShipping()│             │ + calculateShipping()│
└───────┬───────┘                    └───────────┬───────────┘
        │                                       │
        └─────────────────────┬─────────────────┘
                              │ uses
                              ▼
                    ┌─────────────────┐
                    │ Destination     │
                    │ Matching        │
                    │ Logic           │
                    └─────────────────┘
```

### 2.2 Carrier Adapters

```
┌─────────────────────────────────────────────────────────────────┐
│            <<interface>> CarrierAdapterInterface               │
├─────────────────────────────────────────────────────────────────┤
│ + getName(): string                                             │
│ + supportsGuestQuotes(): bool                                   │
│ + getRates(array from, array to, array parcel, array opts): []  │
│ + validateConfig(): bool                                        │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ implements
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
┌───────┴───────┐    ┌────────┴────────┐    ┌──────┴──────┐
│CanadaPost     │    │FedEx_Canada     │    │UPS_Canada   │
│Adapter       │    │Adapter          │    │Adapter      │
├──────────────┤    ├─────────────────┤    ├─────────────┤
│- apiKey      │    │- apiKey         │    │- apiKey     │
│- customerNo  │    │- accountNo      │    │- accountNo  │
│- contractId  │    │- testMode       │    │- testMode   │
│- testMode    │    │- apiUrl         │    │- apiUrl     │
├──────────────┤    ├─────────────────┤    ├─────────────┤
│+ getRates() │    │+ getRates()     │    │+ getRates() │
│+ getName()  │    │+ getName()      │    │+ getName()  │
└─────────────┘    └─────────────────┘    └─────────────┘
```

### 2.3 CanadianShippingCalculator

```
┌─────────────────────────────────────────────────────────────────┐
│                 CanadianShippingCalculator                        │
├─────────────────────────────────────────────────────────────────┤
│ - carriers: CarrierAdapterInterface[]                           │
│ - storeAddress: array                                           │
│ - parcelDefaults: array                                         │
├─────────────────────────────────────────────────────────────────┤
│ + registerCarrier(CarrierAdapterInterface): void                │
│ + setStoreAddress(array): void                                   │
│ + getQuotes(array customer, array parcel, array opts): array[]  │
│ + getCheapestQuote(array customer, array parcel, array opts): ?│
│ + getCarrierQuotes(string carrier, array customer, ...): array[]│
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ orchestrates
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                 Rate Aggregation Flow                           │
│                                                                  │
│  Customer Address ──┬──▶ CanadaPostAdapter ──▶ Rate Quote       │
│                    │                                            │
│  Parcel Details ───┼──▶ FedExAdapter ───────▶ Rate Quote       │
│                    │                                            │
│  Shipping Options ─┼──▶ UPSAdapter ────────▶ Rate Quote         │
│                    │                                            │
│                    └──▶ PurolatorAdapter ─▶ Rate Quote         │
│                                                                  │
│  All Quotes ───▶ Sort by Cost ───▶ Return Sorted List           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Data Flow

### 3.1 Rate Calculation Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    Rate Calculation Sequence                        │
└─────────────────────────────────────────────────────────────────────────┘

  FrontController           ShippingCalculator      ShippingMethod[]
        │                          │                      │
        │ calculateRates(context)  │                      │
        │────────────────────────▶│                      │
        │                          │                      │
        │                          │ foreach methods      │
        │                          │────────────────────▶│
        │                          │                      │
        │                          │   isEnabled()        │
        │                          │◀────────────────────│
        │                          │                      │
        │                          │   isAvailableFor...  │
        │                          │◀────────────────────│
        │                          │                      │
        │                          │   calculateShipping │
        │                          │◀────────────────────│
        │                          │                      │
        │                          │   collect rates      │
        │                          │◀────────────────────│
        │                          │                      │
        │                     sort by cost               │
        │                          │                      │
        │◀────────────────────────│                      │
        │    [rates sorted]        │                      │
```

### 3.2 Carrier Quote Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                   Carrier Quote Sequence                              │
└─────────────────────────────────────────────────────────────────────────┘

  CheckoutPage        CanadianShippingCalculator    CarrierAdapter[]
        │                    │                          │
        │  getQuotes(customer, parcel, options)          │
        │───────────────────▶│                          │
        │                    │                          │
        │                    │  foreach carriers       │
        │                    │───────────────────────▶│
        │                    │                          │
        │                    │  validateConfig()        │
        │                    │◀───────────────────────│
        │                    │                          │
        │                    │  [if valid] getRates()  │
        │                    │◀───────────────────────│
        │                    │                          │
        │                    │  collect quotes         │
        │                    │◀───────────────────────│
        │                    │                          │
        │◀───────────────────│                          │
        │  [all carrier quotes]                         │
```

### 3.3 Package Data Structure

```php
// Package Input
$package = [
    'weight' => 2.5,                    // kg
    'dimensions' => [
        'length' => 30,                  // cm
        'width' => 20,                   // cm
        'height' => 15                   // cm
    ],
    'items' => [
        ['sku' => 'PROD-001', 'qty' => 2],
        ['sku' => 'PROD-002', 'qty' => 1]
    ],
    'destination' => [
        'country' => 'CA',
        'state' => 'ON',
        'postcode' => 'M5V 2T6',
        'city' => 'Toronto'
    ]
];

// Rate Output
$rate = [
    'method_id' => 'flat_rate',
    'method_title' => 'Flat Rate',
    'rate_id' => 'flat_rate:flat_rate',
    'title' => 'Flat Rate',
    'cost' => 9.99,
    'taxes' => [
        'CA' => 1.30
    ],
    'package' => null
];
```

---

## 4. Interface Contracts

### 4.1 ShippingMethodInterface

```php
interface ShippingMethodInterface {
    /**
     * Unique identifier for this method
     */
    public function getId(): string;
    
    /**
     * Display title for checkout
     */
    public function getTitle(): string;
    
    /**
     * Whether method is active
     */
    public function isEnabled(): bool;
    
    /**
     * Check if available for destination
     * @param array $destination ['country', 'state', 'postcode', 'city']
     */
    public function isAvailableForDestination(array $destination): bool;
    
    /**
     * Calculate rates for packages
     * @return array [{'id', 'label', 'cost', 'taxes', 'package'}]
     */
    public function calculateShipping(array $packages, array $context): array;
}
```

### 4.2 CarrierAdapterInterface

```php
interface CarrierAdapterInterface {
    /**
     * Carrier display name
     */
    public function getName(): string;
    
    /**
     * Whether guest quotes are supported
     */
    public function supportsGuestQuotes(): bool;
    
    /**
     * Get shipping rate quotes
     * @param array $from Store address
     * @param array $to Customer address
     * @param array $parcel Weight, dimensions, parcel class
     * @param array $options Signature, insurance, etc.
     * @return array [{'service_code', 'service_name', 'rate', 'currency', 'transit_days'}]
     */
    public function getRates(array $from, array $to, array $parcel, array $options = []): array;
    
    /**
     * Validate API configuration
     */
    public function validateConfig(): bool;
}
```

---

## 5. Error Handling

### 5.1 Error Response Strategy

| Error Type | Response | Logging |
|------------|----------|---------|
| Invalid API credentials | Skip carrier, continue others | Error log |
| API timeout | Skip carrier, continue others | Warning log |
| Invalid destination | Return no rates | Debug log |
| Rate calculation failure | Return empty rates | Error log |

### 5.2 Carrier Failure Handling

```php
try {
    $rates = $carrier->getRates($from, $to, $parcel, $options);
    if (!empty($rates)) {
        $allQuotes[$carrierName] = [
            'carrier' => $carrierName,
            'supports_guest' => $carrier->supportsGuestQuotes(),
            'rates' => $rates
        ];
    }
} catch (\Exception $e) {
    // Log error but continue with other carriers
    error_log("Error getting quotes from {$carrierName}: " . $e->getMessage());
}
```

---

## 6. Configuration Schema

### 6.1 Carrier Configuration

```php
// config/carriers.php
return [
    'canadapost' => [
        'enabled' => true,
        'api_key' => 'YOUR_API_KEY',
        'customer_number' => 'CPC_CUSTOMER_NO',
        'contract_id' => '', // Optional for non-contract rates
        'test_mode' => false,
        'api_url' => 'https://soa-gw.canadapost.ca/rs/'
    ],
    'fedex' => [
        'enabled' => true,
        'api_key' => 'YOUR_KEY',
        'account_no' => 'XXXXXXXX',
        'test_mode' => false
    ],
    // ... other carriers
];
```

### 6.2 Store Address Configuration

```php
// config/store_address.php
return [
    'name' => 'Warehouse',
    'street' => '123 Main Street',
    'city' => 'Toronto',
    'state' => 'ON',
    'post_code' => 'M5V 2T6',
    'country' => 'CA',
    'phone' => '+1-416-555-0100'
];
```

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*