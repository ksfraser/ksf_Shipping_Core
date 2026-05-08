# Integration Testing with Canada Post

## Prerequisites

### 1. Get Canada Post API Credentials

1. Visit [Canada Post Developer Program](https://developer.canadapost.ca/)
2. Sign up for a developer account
3. Create a new application
4. Get your **API Key** and **Customer Number**
5. Optional: Get your **Contract ID** (for negotiated rates)

### 2. Set Environment Variables

```bash
export CP_API_KEY="your-api-key-here"
export CP_CUSTOMER_NUMBER="123456"
export CP_CONTRACT_ID="optional-contract-id"
```

Or create a `.env` file:

```bash
CP_API_KEY=your-api-key-here
CP_CUSTOMER_NUMBER=123456
CP_CONTRACT_ID=optional-contract-id
```

## Running Integration Tests

### Run All Tests (Unit + Integration)
```bash
./vendor/bin/phpunit
```

### Run Only Integration Tests
```bash
./vendor/bin/phpunit --group integration
```

### Run Only Unit Tests (No credentials needed)
```bash
./vendor/bin/phpunit --exclude-group integration
```

### Run Specific Test
```bash
./vendor/bin/phpunit --filter testCanadaPostGetRatesFromLiveAPI
```

## Test Output Example

```
Canada Post Rates:
  Expedited Parcel (DOM.EP): $12.34 CAD
  Xpresspost (DOM.XP): $18.45 CAD
  Priority (DOM.PC): $28.90 CAD
  Regular Parcel (DOM.RP): $9.99 CAD
```

## Troubleshooting

### "No Canada Post API credentials provided"
→ Set the environment variables as shown above

### "Canada Post API error: HTTP 401"
→ Invalid API key - check credentials at developer.canadapost.ca

### "Canada Post API error: HTTP 403"
→ Customer number mismatch or account not activated

### Connection timeout
→ Check firewall allows outbound HTTPS (port 443) to `soa-gw.canadapost.ca`

## Writing New Integration Tests

```php
/**
 * @group integration
 */
public function testMyNewCarrier(): void {
    $apiKey = getenv('MY_CARRIER_KEY') ?: ($_ENV['MY_CARRIER_KEY'] ?? '');
    
    if (empty($apiKey)) {
        $this->markTestSkipped('No credentials provided.');
    }
    
    // Your test here...
}
```

## CI/CD Integration

For GitHub Actions, add secrets:
- `CP_API_KEY`
- `CP_CUSTOMER_NUMBER`

Then in your workflow:
```yaml
- name: Run Integration Tests
  run: ./vendor/bin/phpunit --group integration
  env:
    CP_API_KEY: ${{ secrets.CP_API_KEY }}
    CP_CUSTOMER_NUMBER: ${{ secrets.CP_CUSTOMER_NUMBER }}
```
