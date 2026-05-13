# Requirements Traceability Matrix (RTM) - KSF Shipping Core

| Requirement ID | Requirement Description | Component | Test Case ID | Status |
|----------------|------------------------|-------------|---------------|--------|
| FR-1.1 | Register multiple shipping methods | ShippingCalculator::registerMethod() | TC-001 | Pending |
| FR-1.2 | Methods have unique IDs | ShippingCalculator::registerMethod() | TC-002 | Pending |
| FR-1.3 | Enable/disable methods | ShippingMethodInterface::isEnabled() | TC-003 | Pending |
| FR-1.4 | Configurable method titles | ShippingMethodInterface::getTitle() | TC-004 | Pending |
| FR-2.1 | Calculate rates for multiple packages | ShippingCalculator::calculateRates() | TC-005 | Pending |
| FR-2.2 | Return rate ID, label, cost, taxes | ShippingMethodInterface::calculateShipping() | TC-006 | Pending |
| FR-2.3 | Aggregate rates from all methods | ShippingCalculator::calculateRates() | TC-007 | Pending |
| FR-2.4 | Sort rates by cost ascending | ShippingCalculator::calculateRates() | TC-008 | Pending |
| FR-3.1 | Validate availability by destination | ShippingMethodInterface::isAvailableForDestination() | TC-009 | Pending |
| FR-3.2 | Wildcard matching for postcodes | FlatRateMethod::matchDestination() | TC-010 | Pending |
| FR-3.3 | Configurable allowed destinations | FlatRateMethod::isAvailableForDestination() | TC-011 | Pending |
| FR-4.1 | Flat rate per package | FlatRateMethod::calculateShipping() | TC-012 | Pending |
| FR-4.2 | Additional cost per weight unit | FlatRateMethod::calculateShipping() | TC-013 | Pending |
| FR-4.3 | Flat rate added for each package | FlatRateMethod::calculateShipping() | TC-014 | Pending |
| FR-5.1 | Free shipping when cart total > threshold | FreeShippingMethod::calculateShipping() | TC-015 | Pending |
| FR-5.2 | Configurable minimum amount | FreeShippingMethod::__construct() | TC-016 | Pending |
| FR-5.3 | Hide free shipping if threshold not met | FreeShippingMethod::calculateShipping() | TC-017 | Pending |
| FR-6.1 | Accept packages with weight/dimensions | ShippingCalculator::addPackage() | TC-018 | Pending |
| FR-6.2 | Methods use package data for rates | ShippingMethodInterface::calculateShipping() | TC-019 | Pending |
| FR-6.3 | Support multiple packages per shipment | ShippingCalculator::calculateRates() | TC-020 | Pending |
| NFR-1.1 | Rate calculation performance | Performance test | TC-021 | Pending |
| NFR-1.2 | O(1) destination validation | FlatRateMethod::matchDestination() | TC-022 | Pending |
| NFR-2.1 | Add new methods via interface | ShippingMethodInterface | TC-023 | Pending |
| NFR-2.2 | Configurable methods via array/JSON | Method constructors | TC-024 | Pending |
| NFR-3.1 | PHP 7.3+ compatibility | All classes | TC-026 | Pending |
| NFR-3.2 | Framework-agnostic | No WordPress deps | TC-027 | Pending |
| NFR-3.3 | PSR-4 autoloading | composer.json | TC-028 | Pending |
| DR-1 | Shipping Method data structure | ShippingMethodInterface | TC-029 | Pending |
| DR-2 | Package data structure | ShippingCalculator::addPackage() | TC-030 | Pending |
| DR-3 | Destination data structure | ShippingMethodInterface::isAvailableForDestination() | TC-031 | Pending |
| DR-4 | Shipping Rate data structure | ShippingCalculator::calculateRates() | TC-032 | Pending |

## Test Case Summary
- Total Test Cases: 32
- Implemented: 0
- Pending: 32
- Pass Rate: TBD

*Document Version: 1.0.0*
*Last Updated: 2026-05-12*
