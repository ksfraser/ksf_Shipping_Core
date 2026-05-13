# Shipping Calculation Core - UAT Plan

## Document Information

| Field | Value |
|-------|-------|
| **Module Name** | ksf_Shipping_Core |
| **Document Type** | User Acceptance Test Plan |
| **Version** | 1.0.0 |

---

## 1. UAT Objectives

| Objective | Description | Success Criteria |
|-----------|-------------|------------------|
| **Business Value Validation** | Verify module delivers shipping calculation business value | All business requirements met |
| **Integration Validation** | Verify module integrates correctly with carrier APIs | Real API responses match expected format |
| **Performance Validation** | Verify response times meet SLA requirements | < 500ms for 5 carriers |
| **Error Handling Validation** | Verify graceful degradation on carrier failures | Other carriers continue on one failure |
| **Data Accuracy Validation** | Verify rate calculations are accurate | Rates match carrier portal quotes |

---

## 2. UAT Scope

### 2.1 In Scope

- Manual testing of shipping rate calculation in integration environment
- Real carrier API responses (sandbox/test mode)
- End-to-end calculation flow from package to rate display
- Error scenario testing with carrier API failures

### 2.2 Out of Scope

- Unit testing (covered by Test Plan)
- Platform UI components (separate UAT)
- Production carrier API transactions
- Load testing (separate performance test plan)

---

## 3. Test Environments

### 3.1 Environment Configuration

| Environment | Purpose | Carrier APIs |
|-------------|---------|--------------|
| **Development** | Initial integration testing | Mock responses |
| **Staging** | Full UAT execution | Sandbox/Test APIs |
| **Production** | Final validation | Live APIs (limited) |

### 3.2 Test Account Credentials

| Carrier | Test Account | Environment |
|---------|-------------|-------------|
| Canada Post | CPC_TEST_KEY | Sandbox |
| FedEx | FedEx Test Account | Sandbox |
| UPS | UPS Test Account | Developer Portal |

---

## 4. UAT Scenarios

### 4.1 Scenario: Basic Shipping Rate Calculation

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-001 |
| **Scenario Name** | Calculate Shipping Rates for Domestic Order |
| **Priority** | Critical |
| **Prerequisites** | Module installed, carrier credentials configured |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Configure store address as Toronto, ON warehouse | Configuration saved |
| 2 | Register FlatRateMethod with $9.99 cost | Method registered |
| 3 | Register FreeShippingMethod with $100 minimum | Method registered |
| 4 | Create order with 1 item (weight 2kg, 30x20x15 cm) | Order created |
| 5 | Set destination to Vancouver, BC | Destination set |
| 6 | Set cart total to $75 | Cart total set |
| 7 | Execute calculateRates() | Both flat rate and free shipping checked |
| 8 | Verify Flat Rate $9.99 is returned | Correct rate |
| 9 | Verify Free Shipping is NOT returned (below min) | Correct behavior |
| 10 | Change cart total to $150 | Cart total updated |
| 11 | Execute calculateRates() | Free shipping eligible |
| 12 | Verify both rates returned, sorted by cost | Free ($0) first, Flat ($9.99) second |

**Pass Criteria:**
- [ ] Flat Rate returns $9.99
- [ ] Free Shipping correctly ineligible at $75 cart total
- [ ] Free Shipping correctly eligible at $150 cart total
- [ ] Rates sorted by cost ascending

---

### 4.2 Scenario: Multiple Carrier Quote Comparison

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-002 |
| **Scenario Name** | Compare Rates from Multiple Carriers |
| **Priority** | Critical |
| **Prerequisites** | Multiple carrier adapters registered with valid credentials |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Register Canada Post adapter with test credentials | Carrier registered |
| 2 | Register FedEx adapter with test credentials | Carrier registered |
| 3 | Set destination to Toronto, ON (M5V 2T6) | Destination set |
| 4 | Set parcel: 3kg, 40x30x20 cm | Parcel configured |
| 5 | Request getQuotes() for all carriers | All configured carriers queried |
| 6 | Verify Canada Post quotes include Expedited, Xpresspost, Priority, Regular | All service types returned |
| 7 | Verify FedEx quotes include applicable services | FedEx services returned |
| 8 | Verify quotes are grouped by carrier | Grouping correct |
| 9 | Execute getCheapestQuote() | Single cheapest quote returned |
| 10 | Verify cheapest quote includes carrier name, rate, service code, transit days | All fields present |

**Pass Criteria:**
- [ ] Quotes returned from all valid carriers
- [ ] Quotes grouped by carrier
- [ ] Cheapest quote correctly identified
- [ ] Transit days included in response

---

### 4.3 Scenario: Carrier Configuration Validation

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-003 |
| **Scenario Name** | Graceful Handling of Invalid Carrier Config |
| **Priority** | High |
| **Prerequisites** | Mixed valid/invalid carrier configurations |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Configure Canada Post with valid test API key | Configuration valid |
| 2 | Configure FedEx with empty/invalid API key | Configuration invalid |
| 3 | Request getQuotes() | Only Canada Post quotes returned |
| 4 | Verify FedEx is silently skipped | No error thrown |
| 5 | Check system logs | FedEx validation failure logged |
| 6 | Correct FedEx configuration | Configuration updated |
| 7 | Request getQuotes() again | Both carriers return quotes |

**Pass Criteria:**
- [ ] Invalid carrier skipped without error
- [ ] Other carriers continue working
- [ ] Errors logged for debugging

---

### 4.4 Scenario: Destination-Based Method Restrictions

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-004 |
| **Scenario Name** | Flat Rate Restricted to Specific Regions |
| **Priority** | High |
| **Prerequisites** | FlatRateMethod configured with destination restrictions |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Configure FlatRateMethod with restriction: Ontario only | Restriction set |
| 2 | Set destination to Toronto, ON | Destination within restriction |
| 3 | Calculate rates | Flat rate available |
| 4 | Set destination to Vancouver, BC | Destination outside restriction |
| 5 | Calculate rates | Flat rate NOT available |
| 6 | Update restriction to postcode pattern "M*" (Toronto) | Restriction updated |
| 7 | Set destination to M5V 2T6 | Postcode matches |
| 8 | Calculate rates | Flat rate available |
| 9 | Set destination to L5V 1A1 | Postcode does not match |
| 10 | Calculate rates | Flat rate NOT available |

**Pass Criteria:**
- [ ] Country restriction enforced
- [ ] State restriction enforced
- [ ] Postcode wildcard pattern matching works

---

### 4.5 Scenario: Shipping Options (Signature, Insurance)

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-005 |
| **Scenario Name** | Request Rates with Shipping Options |
| **Priority** | Medium |
| **Prerequisites** | Canada Post adapter supports options |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Request quotes with standard options | Standard rates returned |
| 2 | Add signature option to request | Signature option added to request |
| 3 | Verify Canada Post API call includes SO option code | Option included |
| 4 | Verify returned rates reflect signature cost | Cost adjusted |
| 5 | Add insurance option | Insurance option added |
| 6 | Verify returned rates reflect insurance cost | Cost adjusted |
| 7 | Remove options | Standard rates returned |

**Pass Criteria:**
- [ ] Options correctly mapped to carrier-specific codes
- [ ] Rates reflect additional option costs

---

### 4.6 Scenario: Error Recovery on API Timeout

| Field | Value |
|-------|-------|
| **Scenario ID** | UAT-SHIP-006 |
| **Scenario Name** | Graceful Degradation on Carrier API Failure |
| **Priority** | High |
| **Prerequisites** | Carrier API simulator configured |

**Steps to Execute:**

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Configure all carriers with valid credentials | All configured |
| 2 | Request quotes - verify all return | All carriers respond |
| 3 | Simulate Canada Post API timeout | API returns timeout |
| 4 | Request quotes again | Other carriers return quotes |
| 5 | Verify Canada Post gracefully skipped | No quotes from Canada Post |
| 6 | Restore Canada Post API | API responding |
| 7 | Request quotes again | All carriers return quotes |

**Pass Criteria:**
- [ ] One carrier failure does not affect others
- [ ] Error logged for carrier failure
- [ ] System continues functioning

---

## 5. Acceptance Criteria Checklist

### 5.1 Functional Acceptance

| Criterion | Description | Status |
|-----------|-------------|--------|
| FA-001 | Shipping rates calculate correctly for flat rate method | [ ] |
| FA-002 | Free shipping eligibility determined by cart total | [ ] |
| FA-003 | Multiple carriers return quotes in grouped format | [ ] |
| FA-004 | Cheapest rate identified correctly across carriers | [ ] |
| FA-005 | Destination restrictions enforced for flat rate | [ ] |
| FA-006 | Invalid carrier configurations skipped gracefully | [ ] |
| FA-007 | API errors do not block other carriers | [ ] |
| FA-008 | Rates sorted by cost ascending | [ ] |

### 5.2 Technical Acceptance

| Criterion | Description | Status |
|-----------|-------------|--------|
| TA-001 | Response time < 500ms with 5 carriers | [ ] |
| TA-002 | API keys not logged or displayed | [ ] |
| TA-003 | All carrier adapter interface methods implemented | [ ] |
| TA-004 | Unit test coverage meets targets | [ ] |

### 5.3 Business Acceptance

| Criterion | Description | Status |
|-----------|-------------|--------|
| BA-001 | Customers can compare shipping rates | [ ] |
| BA-002 | Store can offer free shipping promotions | [ ] |
| BA-003 | Regional shipping restrictions configured | [ ] |
| BA-004 | Multiple carrier options available | [ ] |

---

## 6. Sign-Off Requirements

### 6.1 UAT Completion Criteria

| Milestone | Criteria | Sign-off By |
|-----------|----------|-------------|
| Test Data Preparation | All test accounts configured | QA Lead |
| Environment Setup | Staging environment ready | DevOps |
| Scenario Execution | All scenarios executed | QA Team |
| Defect Resolution | All critical/high defects resolved | Development |
| Performance Validation | Response times within SLA | QA Lead |
| Final Acceptance | All criteria passed | Product Owner |

### 6.2 Defect Severity Definitions

| Severity | Definition | Resolution Required |
|----------|------------|---------------------|
| **Critical** | Module cannot calculate any rates | Before UAT sign-off |
| **High** | One carrier consistently fails | Before UAT sign-off |
| **Medium** | Incorrect rate calculations | Before UAT sign-off |
| **Low** | Logging/formatting issues | Next release |

### 6.3 Sign-Off Template

```
UAT Sign-Off Certification

Module: ksf_Shipping_Core
Version: 1.0.0
UAT Date: ________________

I certify that the UAT has been completed for ksf_Shipping_Core.
All critical and high priority scenarios have passed.
All identified defects have been resolved or accepted with rationale.

Sign-off Authorizations:

Product Owner: _________________ Date: _______
QA Lead: _________________ Date: _______
Development Lead: _________________ Date: _______
```

---

## 7. Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Carrier API changes break integration | Medium | High | Version pinning, change monitoring |
| Rate discrepancies with carrier portals | Medium | Medium | Regular reconciliation testing |
| Performance degradation with many carriers | Low | Medium | Caching, async requests |
| Invalid carrier config causes system error | Low | High | Validate config before use |

---

*Document Version: 1.0.0*  
*Author: KSFII Development Team*