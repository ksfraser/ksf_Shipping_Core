# Shipping Calculation Core - Functional Requirements

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Requirement Type** | Functional Requirements |
| **Version** | 1.0.0 |

---

## 1. Requirements Overview

This document defines the functional requirements for the Shipping Calculation Core module, providing detailed specifications for all features, inputs, outputs, and business rules.

---

## 2. Requirements Specification

### 2.1 Shipping Method Registration

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-001 | The system SHALL allow registration of shipping methods implementing `ShippingMethodInterface` | MUST | Core |
| SHIP-FUNC-002 | The system SHALL maintain a collection of registered methods | MUST | Core |
| SHIP-FUNC-003 | The system SHALL prevent duplicate method registration (same ID) | SHOULD | Core |
| SHIP-FUNC-004 | The system SHALL provide method lookup by ID | MUST | Core |

**Test Scenario SHIP-FUNC-001:**
```
Input:  Valid ShippingMethodInterface implementation
Action: Call registerMethod()
Expected: Method is added to internal collection
```

### 2.2 Package Management

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-010 | The system SHALL accept packages with weight (float, kg) | MUST | Core |
| SHIP-FUNC-011 | The system SHALL accept packages with dimensions [length, width, height] (cm) | MUST | Core |
| SHIP-FUNC-012 | The system SHALL accept packages with items array | MAY | Core |
| SHIP-FUNC-013 | The system SHALL accept packages with destination details | MUST | Core |
| SHIP-FUNC-014 | The system SHALL support multiple packages per calculation | MUST | Core |
| SHIP-FUNC-015 | The system SHALL provide a method to clear all packages | MUST | Core |

**Package Data Structure:**
```php
$package = [
    'weight' => float,           // Required: 0.001 - 1000 kg
    'dimensions' => [            // Required
        'length' => float,        // 1 - 10000 cm
        'width' => float,         // 1 - 10000 cm
        'height' => float        // 1 - 10000 cm
    ],
    'items' => array,            // Optional
    'destination' => [            // Required
        'country' => string,      // ISO 3166-1 alpha-2
        'state' => string,        // Optional
        'postcode' => string,     // Optional
        'city' => string         // Optional
    ]
];
```

### 2.3 Rate Calculation

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-020 | The system SHALL calculate shipping rates for all registered methods | MUST | Core |
| SHIP-FUNC-021 | The system SHALL skip disabled methods during calculation | MUST | Core |
| SHIP-FUNC-022 | The system SHALL skip methods unavailable for destination | MUST | Core |
| SHIP-FUNC-023 | The system SHALL return rates sorted by cost (ascending) | MUST | Core |
| SHIP-FUNC-024 | The system SHALL include method ID, title, rate ID, label, cost, and taxes | MUST | Core |
| SHIP-FUNC-025 | The system SHALL provide a method to get cheapest rate only | MUST | Core |
| SHIP-FUNC-026 | The system SHALL return empty array when no rates available | MUST | Core |

**Rate Output Structure:**
```php
$rates = [
    [
        'method_id' => string,       // Method identifier
        'method_title' => string,     // Display title
        'rate_id' => string,          // Unique rate ID
        'title' => string,            // Rate label
        'cost' => float,              // Shipping cost
        'taxes' => array,             // Tax amounts by jurisdiction
        'package' => mixed            // Package reference (optional)
    ],
    // ... more rates
];
```

### 2.4 Flat Rate Shipping

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-030 | The system SHALL support flat rate shipping with configurable cost | MUST | Method |
| SHIP-FUNC-031 | The system SHALL support destination restrictions by country | MUST | Method |
| SHIP-FUNC-032 | The system SHALL support destination restrictions by state/province | MUST | Method |
| SHIP-FUNC-033 | The system SHALL support destination restrictions by postcode pattern (wildcards) | SHOULD | Method |
| SHIP-FUNC-034 | The system SHALL support cost per weight unit surcharge | MAY | Method |
| SHIP-FUNC-035 | The system SHALL support tax calculation for taxable shipments | MAY | Method |
| SHIP-FUNC-036 | The system SHALL apply flat rate to each package in multi-package scenario | MUST | Method |

**Flat Rate Destination Pattern:**
```php
$destinations = [
    ['country' => 'CA'],                          // All Canada
    ['country' => 'CA', 'state' => 'ON'],        // Ontario only
    ['country' => 'CA', 'postcode' => 'M*'],     // Toronto postal codes
];
```

### 2.5 Free Shipping

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-040 | The system SHALL support free shipping with minimum order threshold | MUST | Method |
| SHIP-FUNC-041 | The system SHALL return no rates when cart total below minimum | MUST | Method |
| SHIP-FUNC-042 | The system SHALL return $0 rate when threshold met | MUST | Method |
| SHIP-FUNC-043 | The system SHALL support configurable minimum amount | MUST | Method |

### 2.6 Canadian Carrier Integration

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-050 | The system SHALL register carrier adapters implementing `CarrierAdapterInterface` | MUST | Carrier |
| SHIP-FUNC-051 | The system SHALL validate carrier configuration before quote requests | MUST | Carrier |
| SHIP-FUNC-052 | The system SHALL gracefully skip misconfigured carriers | MUST | Carrier |
| SHIP-FUNC-053 | The system SHALL return quotes from all valid carriers | MUST | Carrier |
| SHIP-FUNC-054 | The system SHALL group quotes by carrier name | MUST | Carrier |
| SHIP-FUNC-055 | The system SHALL include service code, service name, rate, currency, transit days | MUST | Carrier |
| SHIP-FUNC-056 | The system SHALL handle API errors without affecting other carriers | MUST | Carrier |

### 2.7 Canada Post Adapter

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-060 | The system SHALL support Canada Post domestic services | MUST | Carrier |
| SHIP-FUNC-061 | The system SHALL request Expedited Parcel (DOM.EP) rates | MUST | Carrier |
| SHIP-FUNC-062 | The system SHALL request Xpresspost (DOM.XP) rates | MUST | Carrier |
| SHIP-FUNC-063 | The system SHALL request Priority (DOM.PC) rates | MUST | Carrier |
| SHIP-FUNC-064 | The system SHALL request Regular Parcel (DOM.RP) rates | MUST | Carrier |
| SHIP-FUNC-065 | The system SHALL request Collect (DOM.CP) rates | SHOULD | Carrier |
| SHIP-FUNC-066 | The system SHALL support signature option (SO) | MAY | Carrier |
| SHIP-FUNC-067 | The system SHALL support insurance option (COV) | MAY | Carrier |
| SHIP-FUNC-068 | The system SHALL support delivery confirmation option (DC) | MAY | Carrier |
| SHIP-FUNC-069 | The system SHALL normalize postal codes before API request | MUST | Carrier |
| SHIP-FUNC-070 | The system SHALL require API key and customer number | MUST | Carrier |

**Canada Post Service Mapping:**
| Service Code | Service Name |
|--------------|--------------|
| DOM.EP | Expedited Parcel |
| DOM.XP | Xpresspost |
| DOM.PC | Priority |
| DOM.RP | Regular Parcel |
| DOM.CP | Collect |

### 2.8 Additional Carrier Adapters

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-080 | The system SHALL provide FedEx Canada adapter | SHOULD | Carrier |
| SHIP-FUNC-081 | The system SHALL provide UPS Canada adapter | SHOULD | Carrier |
| SHIP-FUNC-082 | The system SHALL provide Purolator adapter | SHOULD | Carrier |
| SHIP-FUNC-083 | The system SHALL provide DHL Canada adapter | SHOULD | Carrier |
| SHIP-FUNC-084 | The system SHALL provide Canpar adapter | SHOULD | Carrier |

### 2.9 Store Address Configuration

| Req ID | Requirement | Priority | Category |
|--------|-------------|----------|----------|
| SHIP-FUNC-090 | The system SHALL accept store address for shipping origin | MUST | Config |
| SHIP-FUNC-091 | The system SHALL use store address as 'from' in carrier API requests | MUST | Config |
| SHIP-FUNC-092 | The system SHALL support configurable parcel defaults | MAY | Config |

---

## 3. Calculation Context

### 3.1 Context Input Structure

```php
$context = [
    'destination' => [
        'country' => 'CA',
        'state' => 'ON',
        'postcode' => 'M5V 2T6',
        'city' => 'Toronto'
    ],
    'user_id' => 123,                    // Optional
    'cart_total' => 150.00,              // For free shipping eligibility
    'customer_type' => 'business',      // Optional: business or consumer
    'shipping_date' => '2026-05-15'     // Optional: for transit calculation
];
```

---

## 4. Validation Rules

### 4.1 Package Validation

| Rule | Condition | Error |
|------|-----------|-------|
| Weight | weight > 0 and weight <= 1000 | Invalid weight |
| Length | length > 0 | Invalid dimensions |
| Width | width > 0 | Invalid dimensions |
| Height | height > 0 | Invalid dimensions |
| Destination | destination array required | Missing destination |

### 4.2 Carrier Configuration Validation

| Rule | Condition | Error |
|------|-----------|-------|
| API Key | Not empty | Missing API key |
| Customer Number | Not empty | Missing customer number |
| Valid URL | Valid URL format | Invalid API URL |

---

## 5. Edge Cases

| ID | Scenario | Expected Behavior |
|----|----------|-------------------|
| EC-001 | No shipping methods registered | Return empty rates array |
| EC-002 | All methods disabled | Return empty rates array |
| EC-003 | All methods unavailable for destination | Return empty rates array |
| EC-004 | Invalid postal code format | Skip carrier, log warning |
| EC-005 | Carrier API timeout (30s) | Skip carrier, log error |
| EC-006 | No carrier adapters registered | Return empty quotes |
| EC-007 | All carrier APIs fail | Return empty quotes |
| EC-008 | Negative amount in credit | Throw InvalidArgumentException |
| EC-009 | Zero amount transfer | Throw InvalidArgumentException |
| EC-010 | Same user transfer | Throw InvalidArgumentException |

---

## 6. Non-Functional Requirements

| Req ID | Requirement | Target |
|--------|-------------|--------|
| SHIP-PERF-001 | Rate calculation response time | < 500ms for 5 carriers |
| SHIP-PERF-002 | Memory usage per calculation | < 10MB |
| SHIP-SEC-001 | API keys must not be logged | Mask in logs |
| SHIP-SEC-002 | Rate data sanitization | XSS prevention |

---

## 7. Requirements Traceability

| Requirement ID | Source | Design Element | Test Case |
|----------------|--------|----------------|----------|
| SHIP-FUNC-001 | Business Requirements | ShippingCalculator::registerMethod() | SHIP-TEST-001 |
| SHIP-FUNC-020 | Business Requirements | ShippingCalculator::calculateRates() | SHIP-TEST-010 |
| SHIP-FUNC-030 | Business Requirements | FlatRateMethod | SHIP-TEST-020 |
| SHIP-FUNC-040 | Business Requirements | FreeShippingMethod | SHIP-TEST-030 |
| SHIP-FUNC-050 | Business Requirements | CanadianShippingCalculator | SHIP-TEST-040 |
| SHIP-FUNC-060 | Business Requirements | CanadaPostAdapter | SHIP-TEST-050 |

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*