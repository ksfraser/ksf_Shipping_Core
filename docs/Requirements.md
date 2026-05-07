# Requirements Document - KSF Shipping Core

## Business Logic Extracted from WooCommerce Shipping Methods

### Functional Requirements

#### FR-1: Shipping Method Management
- **FR-1.1**: System must register multiple shipping methods
- **FR-1.2**: Methods must have unique IDs
- **FR-1.3**: System must support enabling/disabling methods
- **FR-1.4**: Methods must have configurable titles

#### FR-2: Rate Calculation
- **FR-2.1**: System must calculate shipping rates for multiple packages
- **FR-2.2**: Each method must return rate ID, label, cost, and taxes
- **FR-2.3**: System must aggregate rates from all enabled methods
- **FR-2.4**: Rates must be sortable (default: by cost ascending)

#### FR-3: Destination Validation
- **FR-3.1**: Methods must validate availability by destination (country, state, postcode, city)
- **FR-3.2**: System must support wildcard matching for postcodes (e.g., "AB*" matches "AB123")
- **FR-3.3**: Methods can have allowed destinations configured

#### FR-4: Flat Rate Shipping
- **FR-4.1**: System must support flat rate per package
- **FR-4.2**: Flat rate can have additional cost per weight unit
- **FR-4.3**: Flat rate cost is added for each package

#### FR-5: Free Shipping
- **FR-5.1**: System must support free shipping when cart total exceeds threshold
- **FR-5.2**: Minimum amount threshold must be configurable
- **FR-5.3**: Free shipping must be hidden if threshold not met

#### FR-6: Package Handling
- **FR-6.1**: System must accept packages with weight, dimensions, and items
- **FR-6.2**: Methods can use package data for rate calculation
- **FR-6.3**: System must support multiple packages per shipment

### Non-Functional Requirements

#### NFR-1: Performance
- **NFR-1.1**: Rate calculation for 10 packages must complete in <200ms
- **NFR-1.2**: Destination validation must be O(1) for simple rules

#### NFR-2: Extensibility
- **NFR-2.1**: New shipping methods must be addable via interface implementation
- **NFR-2.2**: Methods must be configurable via array/JSON
- **NFR-2.3**: Tax calculation must be pluggable

#### NFR-3: Compatibility
- **NFR-3.1**: Code must run on PHP 7.3+
- **NFR-3.2**: Framework-agnostic (no WordPress/WooCommerce dependencies)
- **NFR-3.3**: PSR-4 autoloading compliant

### Data Requirements

#### DR-1: Shipping Method
- id (string)
- title (string)
- enabled (bool)
- config (array): cost, taxable, destinations, cost_per_weight

#### DR-2: Package
- weight (float, kg)
- dimensions (array: length, width, height in cm)
- items (array of product data)
- value (float, declared value for insurance)

#### DR-3: Destination
- country (string, ISO 3166-1 alpha-2)
- state (string)
- postcode (string)
- city (string)

#### DR-4: Shipping Rate
- method_id (string)
- rate_id (string)
- title (string)
- cost (float)
- taxes (array of tax amounts)
