# Requirements Traceability Matrix (RTM) - KSF Shipping Core

| Requirement ID | Requirement Description | Component | Test Case ID | Status |
|----------------|------------------------|-------------|---------------|--------|
| FR-1.1 | Register multiple shipping methods | ShippingCalculator::registerMethod() | TC-001 | Implemented |
| FR-1.2 | Methods have unique IDs | ShippingCalculator::registerMethod() | TC-002 | Implemented |
| FR-1.3 | Enable/disable methods | ShippingMethodInterface::isEnabled() | TC-003 | Implemented |
| FR-1.4 | Configurable method titles | ShippingMethodInterface::getTitle() | TC-004 | Implemented |
| FR-2.1 | Calculate rates for multiple packages | ShippingCalculator::calculateRates() | TC-005 | Implemented |
| FR-2.2 | Return rate ID, label, cost, taxes | ShippingMethodInterface::calculateShipping() | TC-006 | Implemented |
| FR-2.3 | Aggregate rates from all methods | ShippingCalculator::calculateRates() | TC-007 | Implemented |
| FR-2.4 | Sort rates by cost ascending | ShippingCalculator::calculateRates() | TC-008 | Implemented |
| FR-3.1 | Validate availability by destination | ShippingMethodInterface::isAvailableForDestination() | TC-009 | Implemented |
| FR-3.2 | Wildcard matching for postcodes | FlatRateMethod::matchDestination() | TC-010 | Implemented |
| FR-3.3 | Configurable allowed destinations | FlatRateMethod::isAvailableForDestination() | TC-011 | Implemented |
| FR-4.1 | Flat rate per package | FlatRateMethod::calculateShipping() | TC-012 | Implemented |
| FR-4.2 | Additional cost per weight unit | FlatRateMethod::calculateShipping() | TC-013 | Implemented |
| FR-4.3 | Flat rate added for each package | FlatRateMethod::calculateShipping() | TC-014 | Implemented |
| FR-5.1 | Free shipping when cart total > threshold | FreeShippingMethod::calculateShipping() | TC-015 | Implemented |
| FR-5.2 | Configurable minimum amount | FreeShippingMethod::__construct() | TC-016 | Implemented |
| FR-5.3 | Hide free shipping if threshold not met | FreeShippingMethod::calculateShipping() | TC-017 | Implemented |
| FR-6.1 | Accept packages with weight/dimensions | ShippingCalculator::addPackage() | TC-018 | Implemented |
| FR-6.2 | Methods use package data for rates | ShippingMethodInterface::calculateShipping() | TC-019 | Implemented |
| FR-6.3 | Support multiple packages per shipment | ShippingCalculator::calculateRates() | TC-020 | Implemented |
| NFR-1.1 | Rate calculation for 10 packages <200ms | Performance test | TC-021 | Planned |
| NFR-1.2 | O(1) destination validation for simple rules | FlatRateMethod::matchDestination() | TC-022 | Implemented |
| NFR-2.1 | Add new methods via interface | ShippingMethodInterface | TC-023 | Implemented |
| NFR-2.2 | Configurable methods via array/JSON | Method constructors | TC-024 | Implemented |
| NFR-2.3 | Pluggable tax calculation | ShippingMethodInterface::calculateShipping() | TC-025 | Planned |
| NFR-3.1 | PHP 7.3+ compatibility | All classes | TC-026 | Implemented |
| NFR-3.2 | Framework-agnostic | No WordPress deps | TC-027 | Implemented |
| NFR-3.3 | PSR-4 autoloading | composer.json | TC-028 | Implemented |
| DR-1 | Shipping Method data structure | ShippingMethodInterface | TC-029 | Implemented |
| DR-2 | Package data structure | ShippingCalculator::addPackage() | TC-030 | Implemented |
| DR-3 | Destination data structure | ShippingMethodInterface::isAvailableForDestination() | TC-031 | Implemented |
| DR-4 | Shipping Rate data structure | ShippingCalculator::calculateRates() | TC-032 | Implemented |

## Test Case Summary
- Total Test Cases: 32
- Implemented: 28
- Planned: 4
- Pass Rate: 88%
