# UML Documentation - KSF Shipping Core

## Class Diagram

```
+---------------------+          +---------------------+
| ShippingCalculator   |          | ShippingMethod      |
+---------------------+          | Interface           |
| -methods: array     |          +---------------------+
| -packages: array    |          | +getId()            |
+---------------------+          | +getTitle()         |
| +registerMethod()   |          | +isEnabled()        |
| +addPackage()       |          | +isAvailableForDest()|
| +calculateRates()   |          | +calculateShipping()|
| +getCheapestRate()  |          +---------------------+
| +clearPackages()    |                   ^
+---------------------+                   |
          |                               |
          | uses                            |
          v                     +-----------+-----------+
+---------------------+        |                       |
| ShippingCalculator  |        |                       |
| Context            |   +----+-----+   +-------------+------+
+---------------------+   | Flat Rate |   | Free Shipping       |
| -destination: array|   | Method   |   | Method             |
| -user_id: int      |   +----------+   +--------------------+
| -cart_total: float |   | -cost    |   | -minAmount        |
+---------------------+   | -taxable |   +--------------------+
                            +----------+
```

## Sequence Diagram: Calculate Shipping Rates

```
User -> ShippingCalculator: calculateRates(context)
ShippingCalculator -> ShippingCalculator: availableRates = []
loop for each method in methods
    ShippingCalculator -> ShippingMethod: isEnabled()
    ShippingMethod --> ShippingCalculator: true|false
    alt method is enabled
        ShippingCalculator -> ShippingMethod: isAvailableForDestination(context.destination)
        ShippingMethod --> ShippingCalculator: true|false
        alt destination valid
            ShippingCalculator -> ShippingMethod: calculateShipping(packages, context)
            ShippingMethod --> ShippingCalculator: rates[]
            loop for each rate in rates
                ShippingCalculator -> ShippingCalculator: add to availableRates
            end
        end
    end
end
ShippingCalculator -> ShippingCalculator: sort availableRates by cost ASC
ShippingCalculator --> User: availableRates[]
```

## Sequence Diagram: Flat Rate Method Calculation

```
ShippingCalculator -> FlatRateMethod: calculateShipping(packages, context)
FlatRateMethod -> FlatRateMethod: totalCost = 0
loop for each package in packages
    FlatRateMethod -> FlatRateMethod: totalCost += cost (per package)
    alt cost_per_weight configured
        FlatRateMethod -> FlatRateMethod: totalCost += package.weight * cost_per_weight
    end
end
FlatRateMethod -> FlatRateMethod: create rate {id, label, cost, taxes}
FlatRateMethod --> ShippingCalculator: [rate]
```

## State Diagram: Shipping Method Lifecycle

```
[DISABLED] --> [ENABLED] : enable()
[ENABLED] --> [DISABLED] : disable()
[ENABLED] --> [CALCULATING] : calculateShipping() called
[CALCULATING] --> [READY] : rates returned
[CALCULATING] --> [ERROR] : exception thrown
[ERROR] --> [DISABLED] : auto-disable on error
[READY] --> [ENABLED] : next calculation request
```

## Database Schema (FA Tables - for ksf_FA_Shipping)

```
fa_shipping_methods
------------------
- id (INT, PK, AUTO_INCREMENT)
- method_id (VARCHAR(50), UNIQUE)
- title (VARCHAR(255))
- enabled (TINYINT, DEFAULT 0)
- config (JSON)  -- {cost, taxable, destinations, cost_per_weight}
- created_at (DATETIME)
- updated_at (DATETIME)

fa_shipping_rates (cache/quote table)
---------------------
- id (INT, PK, AUTO_INCREMENT)
- method_id (INT, NOT NULL)
- package_hash (VARCHAR(64))  -- hash of package data for caching
- destination_hash (VARCHAR(64))
- rate_data (JSON)  -- {cost, taxes, label}
- expires_at (DATETIME)
- created_at (DATETIME)

fa_shipping_zones
----------------
- id (INT, PK, AUTO_INCREMENT)
- zone_name (VARCHAR(255))
- locations (JSON)  -- [{country, state, postcode_pattern}]
- method_ids (JSON)  -- [1, 2, 3]
- created_at (DATETIME)
```

## Activity Diagram: Validate Destination

```
[Start] --> [Get method's allowed destinations]
[Get method's allowed destinations] -->|Empty| [Return true (no restrictions)]
[Get method's allowed destinations] -->|Not empty| [Loop through allowed destinations]
[Loop through allowed destinations] --> [Match country]
[Match country] -->|No match| [Next destination]
[Match country] -->|Match| [Match state]
[Match state] -->|No match| [Next destination]
[Match state] -->|Match| [Match postcode]
[Match postcode] -->|No match| [Next destination]
[Match postcode] -->|Match| [Return true]
[Next destination] -->|More destinations| [Match country]
[Next destination] -->|No more| [Return false]
```
