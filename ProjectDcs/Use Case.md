# Shipping Calculation Core - Use Case Specification

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Document Type** | Use Case Specification |
| **Version** | 1.0.0 |

---

## 1. Use Case Overview

| Use Case ID | UC-SHIP-001 |
|-------------|-------------|
| **Use Case Name** | Calculate Shipping Rates |
| **Primary Actor** | E-commerce Checkout System |
| **Secondary Actors** | Shipping Carrier APIs |
| **Brief Description** | System calculates available shipping rates for a customer's order based on destination and package details |
| **Pre-condition** | Customer has items in cart with valid destination address |
| **Post-condition** | Available shipping rates are returned sorted by cost |

---

## 2. Use Cases

### 2.1 UC-SHIP-001: Calculate Shipping Rates

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Checkout System | Provides packages (weight, dimensions, destination) and calculation context |
| 2 | Shipping Calculator | Validates package data completeness |
| 3 | Shipping Calculator | Iterates through registered shipping methods |
| 4 | For each method | Checks if method is enabled |
| 5 | For each method | Checks if method is available for destination |
| 6 | For each method | Calls calculateShipping() |
| 7 | Shipping Calculator | Collects all rate results |
| 8 | Shipping Calculator | Sorts rates by cost (ascending) |
| 9 | Shipping Calculator | Returns sorted rate array |

**Alternative Flows:**

| Step | Branch Condition | Action |
|------|------------------|--------|
| 4a | Method disabled | Skip method, continue to next |
| 5a | Method unavailable for destination | Skip method, continue to next |
| 6a | Rate calculation error | Log error, skip method |
| 6b | No rates returned | Skip, continue to next method |

**Pre-conditions:**
- At least one shipping method is registered
- Package contains valid destination
- Destination is within service area

**Post-conditions:**
- Rates array is returned sorted by cost
- All enabled and available methods have been processed

**Exception Scenarios:**

| Exception | Handling |
|-----------|----------|
| Invalid package data | Throw InvalidArgumentException |
| No methods registered | Return empty array |
| All methods unavailable | Return empty array |

---

### 2.2 UC-SHIP-002: Get Cheapest Rate

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Checkout System | Requests cheapest rate for packages and context |
| 2 | Shipping Calculator | Calls calculateRates() |
| 3 | Shipping Calculator | Returns first rate from sorted array |
| 4 | If rates empty | Returns null |

**Pre-conditions:**
- Valid packages and context provided

**Post-conditions:**
- Single cheapest rate is returned, or null if none available

---

### 2.3 UC-SHIP-003: Get Carrier Quotes

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Checkout System | Provides customer address, parcel details, shipping options |
| 2 | Canadian Shipping Calculator | Iterates through registered carrier adapters |
| 3 | For each carrier | Validates carrier configuration |
| 4 | For valid carriers | Requests rate quotes via getRates() |
| 5 | For each quote | Includes carrier name, service details, rate, currency, transit days |
| 6 | Canadian Shipping Calculator | Groups quotes by carrier |
| 7 | Canadian Shipping Calculator | Returns all quotes grouped |

**Alternative Flows:**

| Step | Branch Condition | Action |
|------|------------------|--------|
| 3a | Invalid configuration | Skip carrier, continue to next |
| 4a | API request fails | Log error, skip carrier, continue |

**Pre-conditions:**
- At least one carrier adapter is registered
- Carrier API credentials are configured

**Post-conditions:**
- Quotes from all valid carriers are returned

---

### 2.4 UC-SHIP-004: Calculate Free Shipping Eligibility

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Checkout System | Calls calculateRates() with cart total in context |
| 2 | Free Shipping Method | Receives cart total from context |
| 3 | Free Shipping Method | Checks if cart_total >= min_amount |
| 4 | If eligible | Returns rate with $0 cost |
| 5 | If not eligible | Returns empty array |

**Pre-conditions:**
- Free shipping method is registered and enabled

**Post-conditions:**
- Free shipping rate returned only when threshold met

---

### 2.5 UC-SHIP-005: Calculate Flat Rate with Destination

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Flat Rate Method | Receives destination for availability check |
| 2 | Flat Rate Method | Checks if destination matches allowed destinations |
| 3 | If no restrictions | Returns flat rate |
| 4 | If restrictions exist | Matches destination against patterns |
| 5 | If match found | Returns flat rate |
| 6 | If no match | Returns empty array |

**Alternative Flows:**

| Step | Branch Condition | Action |
|------|------------------|--------|
| 4a | Country mismatch | No match |
| 4b | State mismatch | No match |
| 4c | Postcode pattern mismatch | No match |

**Pre-conditions:**
- Flat rate method is registered and enabled
- Destination is provided

**Post-conditions:**
- Flat rate returned only for matching destinations

---

### 2.6 UC-SHIP-006: Add Shipping Options

**Basic Flow:**

| Step | Actor | Action |
|------|-------|--------|
| 1 | Checkout System | Provides options array with signature, insurance, etc. |
| 2 | Carrier Adapter | Receives options in getRates() call |
| 3 | Carrier Adapter | Maps options to carrier-specific option codes |
| 4 | Carrier Adapter | Includes options in API request |
| 5 | Carrier Adapter | Returns rates including selected options |

**Options Mapping:**

| Option | Canada Post Code | Description |
|--------|-----------------|-------------|
| signature | SO | Signature confirmation |
| insurance | COV | Coverage/insurance |
| delivery_confirmation | DC | Delivery confirmation |

**Pre-conditions:**
- Carrier adapter supports requested options

**Post-conditions:**
- Rates reflect additional option costs

---

## 3. Actor Definitions

| Actor | Role | System Interface |
|-------|------|------------------|
| **E-commerce Checkout System** | Primary actor initiating rate calculations | ShippingCalculator::calculateRates() |
| **Customer** | End user whose order is being shipped | Indirect via checkout system |
| **Shipping Carrier APIs** | External systems providing rate quotes | CarrierAdapterInterface implementations |

---

## 4. Activity Diagrams

### 4.1 Rate Calculation Flow

```
┌─────────────┐
│   Start     │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│  Get Packages &     │
│  Context            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐     ┌─────────────────┐
│  Get Registered     │     │  Validate       │
│  Methods            │     │  Package        │
└──────────┬──────────┘     └───────┬─────────┘
           │                        │
           │                  ┌─────┴─────┐
           │                  │ Valid?    │
           │                  └─────┬─────┘
           │                   Yes  │  No
           │                  ┌────┴────┐
           │                  ▼         ▼
           │            ┌────────┐  ┌──────────┐
           │            │Continue│  │Throw    │
           │            └────────┘  │Exception│
           │                        └─────────┘
           ▼
┌─────────────────────┐
│  Loop: For Each     │
│  Shipping Method    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐     ┌─────────────────┐
│  Method Enabled?    │     │  End (No Rates) │
└──────────┬──────────┘     └─────────────────┘
           │
      ┌────┴────┐
      │ Yes     │ No
      ▼         ▼
┌───────────┐ ┌────────┐
│ Continue  │ │ Skip   │
└─────┬─────┘ └───┬───┘
      │           │
      ▼           │
┌─────────────────────┐
│  Available for      │
│  Destination?       │
└──────────┬──────────┘
           │
      ┌────┴────┐
      │ Yes     │ No
      ▼         ▼
┌───────────┐ ┌────────┐
│ Continue  │ │ Skip   │
└─────┬─────┘ └───┬───┘
      │           │
      ▼           │
┌─────────────────────┐
│  Calculate Shipping │
│  Rates              │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Collect Rates      │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  More Methods?      │
└──────────┬──────────┘
           │
      ┌────┴────┐
      │ Yes     │ No
      ▼         ▼
┌───────────┐ ┌────────┐
│  Loop     │ │  Sort  │
└───────────┘ │ by Cost│
              └────┬───┘
                   │
                   ▼
              ┌─────────────┐
              │  Return     │
              │  Rates      │
              └─────────────┘
```

---

## 5. Sequence Diagrams

### 5.1 CalculateRates Sequence

```
┌──────────────┐    ┌──────────────────┐    ┌────────────────┐
│   Checkout   │    │ ShippingCalculator│   │ShippingMethod │
│   System     │    │                  │    │               │
└──────┬───────┘    └────────┬─────────┘    └───────┬────────┘
       │                     │                       │
       │ calculateRates()    │                       │
       │────────────────────▶│                       │
       │                     │                       │
       │                     │ foreach methods       │
       │                     │──────────────────────▶│
       │                     │                       │
       │                     │    isEnabled()       │
       │                     │◀──────────────────────│
       │                     │                       │
       │                     │ isAvailableForDest()  │
       │                     │◀──────────────────────│
       │                     │                       │
       │                     │ calculateShipping()   │
       │                     │◀──────────────────────│
       │                     │                       │
       │                     │   [rates]            │
       │                     │◀──────────────────────│
       │                     │                       │
       │   [rates sorted]    │                       │
       │◀────────────────────│                       │
       │                     │                       │
```

---

## 6. Use Case Summary Table

| UC ID | Use Case Name | Primary Actor | Priority |
|-------|--------------|----------------|----------|
| UC-SHIP-001 | Calculate Shipping Rates | Checkout System | Critical |
| UC-SHIP-002 | Get Cheapest Rate | Checkout System | High |
| UC-SHIP-003 | Get Carrier Quotes | Checkout System | Critical |
| UC-SHIP-004 | Calculate Free Shipping Eligibility | Checkout System | High |
| UC-SHIP-005 | Calculate Flat Rate with Destination | Checkout System | High |
| UC-SHIP-006 | Add Shipping Options | Checkout System | Medium |

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*