# Shipping Calculation Core - Business Requirements

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Module Type** | Business Logic / Framework-Agnostic Core |
| **Business Domain** | E-commerce Shipping Rate Calculation |
| **Version** | 1.0.0 |
| **Last Updated** | 2026-05-13 |

---

## 1. Project Overview

### 1.1 Purpose

The Shipping Calculation Core module (`ksf_Shipping_Core`) provides a framework-agnostic shipping rate calculator extracted from WooCommerce Shipping methods. It enables e-commerce platforms to calculate shipping rates from multiple carriers, supporting both simple flat-rate shipping and complex carrier API integrations.

### 1.2 Problem Statement

E-commerce platforms face significant challenges when implementing shipping rate calculations:

1. **Carrier Fragmentation**: Each shipping carrier (Canada Post, FedEx, UPS, Purolator, DHL, Canpar) provides different APIs, rate structures, and service levels
2. **Platform Lock-in**: Historical shipping modules were tightly coupled to specific platforms (WooCommerce), making them non-portable
3. **Rate Comparison**: Customers require the ability to compare rates across multiple carriers before making shipping decisions
4. **Address Restrictions**: Shipping methods must support geographic restrictions (country, state, postal code patterns)
5. **Dynamic Pricing**: Weight-based, dimension-based, and cart-total-based pricing requirements
6. **Multiple Package Support**: Orders containing multiple packages require consolidated rate calculations

### 1.3 Business Context

This module serves as the **business logic layer** in the KSFII architecture pattern:

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   UI Adapter    │────▶│  Business Core  │◀────│  Platform DB    │
│ (ksf_Platform)  │     │(ksf_Shipping)   │     │   Adapter       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 2. Scope

### 2.1 In Scope

#### Core Shipping Calculator
- Registration and management of shipping methods
- Package input handling (weight, dimensions, items, destination)
- Rate calculation across multiple registered methods
- Rate sorting and filtering by cost
- Cheapest rate selection

#### Canadian Carrier Integration
- Canada Post API adapter (contract and non-contract rates)
- FedEx Canada API adapter
- UPS Canada API adapter
- Purolator API adapter
- DHL Canada API adapter
- Canpar API adapter

#### Built-in Shipping Methods
- Flat Rate shipping (fixed cost with optional weight surcharges)
- Free Shipping (minimum order threshold support)

#### Shipping Options
- Signature confirmation
- Insurance coverage
- Delivery confirmation
- Parcel class handling (regular, fragile, hazardous)

### 2.2 Out of Scope

- International shipping (initially limited to Canadian domestic)
- Real-time shipment tracking
- Label generation
- Return shipping processing
- Multi-currency support (CAD only initially)
- Shipping method administration UI
- Order management integration

---

## 3. Features

### 3.1 Shipping Method Registration

| Feature | Description |
|---------|-------------|
| `registerMethod()` | Register shipping methods implementing `ShippingMethodInterface` |
| `isEnabled()` | Enable/disable individual methods |
| `isAvailableForDestination()` | Geographic availability rules |

### 3.2 Rate Calculation

| Feature | Description |
|---------|-------------|
| `calculateRates()` | Calculate all available rates for packages and destination |
| `getCheapestRate()` | Return the lowest-cost rate option |
| `clearPackages()` | Reset for new calculation session |

### 3.3 Canadian Carrier Support

| Feature | Description |
|---------|-------------|
| `registerCarrier()` | Register carrier adapters implementing `CarrierAdapterInterface` |
| `getQuotes()` | Get quotes from all configured carriers |
| `getCheapestQuote()` | Get lowest rate across all carriers |
| `getCarrierQuotes()` | Query specific carrier only |
| `validateConfig()` | Validate carrier API credentials |

### 3.4 Parcel Configuration

| Feature | Description |
|---------|-------------|
| `weight` | Parcel weight in kilograms |
| `length` | Parcel length in centimeters |
| `width` | Parcel width in centimeters |
| `height` | Parcel height in centimeters |
| `parcel_class` | Classification affecting rate (regular, fragile, etc.) |

### 3.5 Shipping Options

| Feature | Description |
|---------|-------------|
| `signature` | Signature upon delivery confirmation |
| `insurance` | Insurance coverage for package value |
| `delivery_confirmation` | Proof of delivery tracking |

---

## 4. Integration Dependencies

### 4.1 External API Dependencies

| Carrier | API Documentation | Authentication |
|---------|------------------|-----------------|
| Canada Post | https://developer.canadapost.ca/ | API Key + Customer Number |
| FedEx | FedEx Developer Portal | OAuth 2.0 / API Key |
| UPS | https://developer.ups.com/ | OAuth 2.0 / API Key |
| Purolator | Purolator E-Ship API | API Key |
| DHL Canada | DHL Developer Portal | API Key |
| Canpar | Canpar Connect | API Key |

### 4.2 Internal Module Dependencies

| Module | Purpose | Dependency Type |
|--------|---------|-----------------|
| `ksf_CRM` | Customer address validation | Optional |
| `ksf_Orders` | Order total and package aggregation | Required |
| Platform Adapter | Tax calculation | Optional |

### 4.3 Configuration Dependencies

```
config/
├── shipping/
│   ├── carriers.php          # Carrier API credentials
│   ├── store_address.php     # Warehouse/origin address
│   ├── parcel_defaults.php   # Default parcel settings
│   └── enabled_methods.php  # Active shipping methods
```

---

## 5. User Stories

### 5.1 Customer Checkout

> **As a** customer  
> **I want to** see available shipping rates from multiple carriers  
> **So that I** can choose the best option based on cost and delivery time

**Acceptance Criteria:**
- All enabled carriers return rate quotes
- Rates are sorted by cost (lowest first)
- Transit days are displayed for each option
- Failed carrier requests don't block other results

### 5.2 Free Shipping Eligibility

> **As a** customer  
> **I want to** see free shipping when my order qualifies  
> **So that I** can save on delivery costs

**Acceptance Criteria:**
- Free shipping appears when cart total meets minimum threshold
- Free shipping is unavailable when below minimum
- Free shipping is listed with $0 cost

### 5.3 Flat Rate Shipping

> **As a** store administrator  
> **I want to** offer flat rate shipping to specific regions  
> **So that I** can provide consistent shipping costs

**Acceptance Criteria:**
- Flat rate is configurable per-region
- Postcode patterns support wildcard matching
- Weight surcharges apply when configured

---

## 6. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Rate calculation response time | < 500ms | Performance profiling |
| Carrier API availability | 99.9% | Uptime monitoring |
| Quote accuracy vs carrier portals | 100% | Sample rate comparison |
| Failed request fallback | Graceful degradation | Error logging |

---

## 7. Glossary

| Term | Definition |
|------|------------|
| **Rate Quote** | Estimated shipping cost from a carrier for a specific service |
| **Transit Days** | Estimated business days for delivery |
| **Parcel Class** | Classification affecting rate (regular, fragile, hazardous) |
| **Shipping Method** | Store-configured shipping option (flat rate, free shipping) |
| **Carrier Adapter** | Interface implementation for third-party carrier APIs |
| **Package** | Physical parcel with weight, dimensions, and destination |

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*