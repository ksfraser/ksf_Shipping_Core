<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * DHL Canada API Adapter
 * Documentation: https://developer.dhl.com/api-reference
 */
class DHL_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $testMode;
    private $apiUrl = 'https://api-eu.dhl.com/parcel/de/shipping/v2/';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://api-sandbox.dhl.com/parcel/de/shipping/v2/';
        }
    }
    
    public function getName(): string {
        return 'DHL Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false;
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        $rates = [];
        $services = [
            'PARCEL' => 'DHL Parcel',
            'PARCEL_INTERNATIONAL' => 'DHL International',
            'EXPRESS' => 'DHL Express',
            'EXPRESS_9' => 'DHL Express 9:00',
            'EXPRESS_12' => 'DHL Express 12:00'
        ];
        
        foreach ($services as $code => $name) {
            $rate = $this->getRateForService($code, $from, $to, $parcel, $options);
            if ($rate !== null) {
                $rates[] = $rate;
            }
        }
        
        return $rates;
    }
    
    private function getRateForService(string $serviceCode, array $from, array $to, array $parcel, array $options): ?array {
        $endpoint = $this->apiUrl . 'rates';
        
        $requestBody = [
            'recipientAddress' => [
                'postalCode' => $this->normalizePostalCode($to['post_code'] ?? ''),
                'city' => $to['city'] ?? '',
                'countryCode' => $to['country'] ?? 'CA'
            ],
            'parcel' => [
                'weight' => max(0.1, $parcel['weight'] ?? 0.1),
                'dimensions' => [
                    'length' => max(1, $parcel['length'] ?? 1),
                    'width' => max(1, $parcel['width'] ?? 1),
                    'height' => max(1, $parcel['height'] ?? 1)
                ]
            ],
            'product' => $serviceCode,
            'accountNumber' => $this->config['account_number'] ?? ''
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . ($this->config['api_key'] ?? '')
        ];
        
        $response = $this->makeRequest($endpoint, $requestBody, $headers);
        
        if ($response === false || !isset($response['products'][0]['totalPrice'][0])) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)($response['products'][0]['totalPrice'][0]['price'] ?? 0),
            'currency' => $response['products'][0]['totalPrice'][0]['currencyCode'] ?? 'CAD',
            'transit_days' => (int)($response['products'][0]['deliveryTime']['days'] ?? 0)
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'PARCEL' => 'DHL Parcel',
            'PARCEL_INTERNATIONAL' => 'DHL International',
            'EXPRESS' => 'DHL Express',
            'EXPRESS_9' => 'DHL Express 9:00',
            'EXPRESS_12' => 'DHL Express 12:00'
        ];
        return $names[$code] ?? $code;
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
            error_log("DHL API error: HTTP $httpCode - $response");
            return false;
        }
        
        return json_decode($response, true);
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']);
    }
}
