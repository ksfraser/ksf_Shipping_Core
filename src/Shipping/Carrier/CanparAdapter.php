<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Canpar API Adapter  
 * Documentation: https://www.canpar.com/en/api/
 */
class CanparAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $testMode;
    private $apiUrl = 'https://www.canpar.com/api/';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://sandbox.canpar.com/api/';
        }
    }
    
    public function getName(): string {
        return 'Canpar';
    }
    
    public function supportsGuestQuotes(): bool { 
        return $this->config['guest_quotes'] ?? true; 
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        $rates = [];
        $services = [
            'Ground' => 'Canpar Ground',
            'Express' => 'Canpar Express',
            'ExpressAM' => 'Canpar Express AM',
            'ExpressPM' => 'Canpar Express PM'
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
        $endpoint = $this->apiUrl . 'rates/quote';
        
        $requestBody = [
            'api_key' => $this->config['api_key'] ?? '',
            'from_postal_code' => $this->normalizePostalCode($from['post_code'] ?? ''),
            'to_postal_code' => $this->normalizePostalCode($to['post_code'] ?? ''),
            'weight' => max(0.1, $parcel['weight'] ?? 0.1),
            'length' => max(1, $parcel['length'] ?? 1),
            'width' => max(1, $parcel['width'] ?? 1),
            'height' => max(1, $parcel['height'] ?? 1),
            'service_type' => $serviceCode
        ];
        
        // Add authentication if not guest
        if (!($this->config['guest_quotes'] ?? true)) {
            $requestBody['username'] = $this->config['username'] ?? '';
            $requestBody['password'] = $this->config['password'] ?? '';
        }
        
        $headers = ['Content-Type: application/json'];
        
        $response = $this->makeRequest($endpoint, $requestBody, $headers);
        
        if ($response === false || !isset($response['rate'])) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)($response['rate'] ?? 0),
            'currency' => $response['currency'] ?? 'CAD',
            'transit_days' => (int)($response['transit_days'] ?? 0)
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'Ground' => 'Canpar Ground',
            'Express' => 'Canpar Express',
            'ExpressAM' => 'Canpar Express AM',
            'ExpressPM' => 'Canpar Express PM'
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
            error_log("Canpar API error: HTTP $httpCode - $response");
            return false;
        }
        
        return json_decode($response, true);
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']) || 
               (!empty($this->config['username']) && !empty($this->config['password']));
    }
}
