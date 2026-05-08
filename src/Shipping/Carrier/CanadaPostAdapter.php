<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Canada Post API Adapter
 * Documentation: https://developer.canadapost.ca/
 */
class CanadaPostAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $customerNumber;
    private $apiKey;
    private $contractId;
    private $testMode;
    private $apiUrl = 'https://soa-gw.canadapost.ca/rs/';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->apiKey = $config['api_key'] ?? '';
        $this->customerNumber = $config['customer_number'] ?? '';
        $this->contractId = $config['contract_id'] ?? '';
        $this->testMode = $config['test_mode'] ?? false;
    }
    
    public function getName(): string {
        return 'Canada Post';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires API credentials
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        $rates = [];
        $services = ['DOM.EP', 'DOM.XP', 'DOM.PC', 'DOM.RP', 'DOM.CP']; // Expedited, Xpresspost, Priority, Regular, Collect
        
        foreach ($services as $serviceCode) {
            $rate = $this->getRateForService($serviceCode, $from, $to, $parcel, $options);
            if ($rate !== null) {
                $rates[] = $rate;
            }
        }
        
        return $rates;
    }
    
    private function getRateForService(string $serviceCode, array $from, array $to, array $parcel, array $options): ?array {
        $endpoint = $this->apiUrl . $this->customerNumber . '/rs/ship/price';
        
        $requestBody = [
            'service-code' => $serviceCode,
            'origin-postal-code' => $this->normalizePostalCode($from['post_code'] ?? $from['zip'] ?? ''),
            'destination' => [
                'domestic-address' => [
                    'postal-code' => $this->normalizePostalCode($to['post_code'] ?? $to['zip'] ?? '')
                ]
            ],
            'parcel-characteristics' => [
                'weight' => max(0.001, $parcel['weight'] ?? 0.1),
                'dimensions' => [
                    'length' => max(1, $parcel['length'] ?? 1),
                    'width' => max(1, $parcel['width'] ?? 1),
                    'height' => max(1, $parcel['height'] ?? 1)
                ]
            ],
            'options' => [
                'option' => $this->buildOptions($options)
            ]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->apiKey . ':')
        ];
        
        $response = $this->makeRequest($endpoint, $requestBody, $headers);
        
        if ($response === false || !isset($response['price'])) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)($response['price'][0]['due'][0]['amount'][0] ?? 0),
            'currency' => $response['price'][0]['due'][0]['currency-code'][0] ?? 'CAD',
            'transit_days' => (int)($response['service-standard'][0]['expected-transit-time'][0] ?? 0)
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function buildOptions(array $options): array {
        $apiOptions = [];
        
        if ($options['signature'] ?? false) {
            $apiOptions[] = ['option-code' => 'SO'];
        }
        if ($options['insurance'] ?? false) {
            $apiOptions[] = ['option-code' => 'COV'];
        }
        if ($options['delivery-confirmation'] ?? false) {
            $apiOptions[] = ['option-code' => 'DC'];
        }
        
        return $apiOptions;
    }
    
    private function makeRequest(string $url, array $body, array $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Canada Post API error: HTTP $httpCode - $response");
            return false;
        }
        
        $xml = simplexml_load_string($response);
        return json_decode(json_encode($xml), true);
    }
    
    public function validateConfig(): bool {
        return !empty($this->apiKey) && !empty($this->customerNumber);
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'DOM.EP' => 'Expedited Parcel',
            'DOM.XP' => 'Xpresspost',
            'DOM.PC' => 'Priority',
            'DOM.RP' => 'Regular Parcel',
            'DOM.CP' => 'Collect'
        ];
        return $names[$code] ?? $code;
    }
}
