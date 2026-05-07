# BABOK Documentation - KSF Shipping Core

## Business Analysis Body of Knowledge Mapping

### Business Goals
- **BG-1**: Provide accurate shipping rate calculations
- **BG-2**: Support multiple shipping methods (flat rate, free, real-time carriers)
- **BG-3**: Validate shipping availability by destination
- **BG-4**: Integrate with carrier APIs for real-time rates (future)

### Stakeholders
| Role | Description | Priority |
|------|-------------|----------|
| Customers | Select shipping method at checkout | High |
| Store Owners | Configure shipping methods and rates | High |
| Developers | Integrate shipping calculator with FA/other systems | Medium |
| Carriers | Provide real-time shipping rates (future) | Low |

### Business Requirements (BABOK Task: Define Business Case)

#### BR-1: Shipping Method Management
- **BR-1.1**: System must support multiple shipping methods
- **BR-1.2**: Methods must be enableable/disableable
- **BR-1.3**: Methods must have configurable titles and costs
- **BR-1.4**: System must support method-specific configuration

#### BR-2: Rate Calculation
- **BR-2.1**: System must calculate rates for multiple packages
- **BR-2.2**: Each method must return rate ID, label, cost, and taxes
- **BR-2.3**: System must aggregate rates from all enabled methods
- **BR-2.4**: Rates must be sortable (default: by cost ascending)

#### BR-3: Destination Validation
- **BR-3.1**: Methods must validate availability by destination (country, state, postcode, city)
- **BR-3.2**: System must support wildcard matching for postcodes (e.g., "AB*" matches "AB123")
- **BR-3.3**: Methods can have allowed destinations configured
- **BR-3.4**: System must handle international shipping restrictions

#### BR-4: Flat Rate Shipping
- **BR-4.1**: System must support flat rate per package
- **BR-4.2**: Flat rate can have additional cost per weight unit
- **BR-4.3**: Flat rate cost is added for each package

#### BR-5: Free Shipping
- **BR-5.1**: System must support free shipping when cart total exceeds threshold
- **BR-5.2**: Minimum amount threshold must be configurable
- **BR-5.3**: Free shipping must be hidden if threshold not met

### Solution Assessment (BABOK Task: Assess Proposed Solution)

#### Current State (WooCommerce Shipping Analysis)
- ✅ Mature shipping API with method registration
- ✅ Supports flat rate, free shipping, and real-time carriers
- ❌ Tightly coupled to WooCommerce cart/package objects
- ❌ Shipping methods extend WC_Shipping_Method (WordPress specific)
- ❌ Tax calculation embedded in WooCommerce

#### Future State (KSF Shipping Core)
- ✅ Framework-agnostic (PSR-4, PHP 7.3+)
- ✅ Interface-based design (ShippingMethodInterface)
- ✅ Package-agnostic (array-based package definition)
- ✅ Destination validation with wildcard support
- ✅ FA integration via separate ksf_FA_Shipping module

### Transition Requirements
- **TR-1**: FA integration module must pass FA cart/order data as packages
- **TR-2**: Shipping methods must be stored in FA database (new table: fa_shipping_methods)
- **TR-3**: Admin UI for method configuration must be built in FA
- **TR-4**: Real-time carrier integration (FedEx, UPS) via separate adapters

### Risk Analysis
| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Incorrect rate calculation | High | Low | TDD with PHPUnit test coverage |
| Destination validation errors | Medium | Low | Thorough testing of wildcard patterns |
| Carrier API downtime | High | Medium | Fallback to flat rate, caching |
| Package dimension/weight errors | Medium | Medium | Validation before calculation |

### Performance Metrics
- Rate calculation: <200ms for 10 packages (target)
- Destination validation: O(1) for simple rules, O(n) for complex
- Carrier API calls: <2 seconds timeout with fallback

### Compliance Requirements
- **Tax Compliance**: Shipping taxes must be calculated per jurisdiction
- **Audit**: All shipping methods used must be logged for order audit trail
- **Transparency**: Customers must see shipping costs before checkout
