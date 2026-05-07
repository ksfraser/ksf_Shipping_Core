<?php
namespace Ksf\Shipping;

/**
 * DHL Canada API Integration
 * Documentation: https://developer.dhl.com/api-reference
 */
class DHL_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://api-eu.dhl.com/parcel/de/shipping/v2/';
    private $testMode;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://api-sandbox.dhl.com/parcel/de/shipping/v2/';
        }
    }
    
    public function getName(): string { return 'DHL Canada'; }
    public function supportsGuestQuotes(): bool { return false; }
    
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

/**
 * Purolator API Integration
 * Documentation: https://docs.purolator.com/
 */
class PurolatorAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://webservices.purolator.com/Estimating/EstimatingService.asmx';
    private $testMode;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://devwebservices.purolator.com/Estimating/EstimatingService.asmx';
        }
    }
    
    public function getName(): string { return 'Purolator'; }
    public function supportsGuestQuotes(): bool { return false; }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        $rates = [];
        $services = [
            'PurolatorExpress' => 'Purolator Express',
            'PurolatorGround' => 'Purolator Ground',
            'PurolatorExpress9AM' => 'Purolator Express 9AM',
            'PurolatorExpress1030AM' => 'Purolator Express 10:30AM',
            'PurolatorExpressEvening' => 'Purolator Express Evening'
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
        // Purolator uses SOAP API
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <RequestContext xmlns="http://purolator.com/ws/estimating/">
      <Version>1.0</Version>
    </RequestContext>
  </soap:Header>
  <soap:Body>
    <GetQuickEstimate xmlns="http://purolator.com/ws/estimating/">
      <request>
        <BillingAccountNumber>' . ($this->config['account_number'] ?? '') . '</BillingAccountNumber>
        <SenderPostalCode>' . $this->normalizePostalCode($from['post_code'] ?? '') . '</SenderPostalCode>
        <ReceiverPostalCode>' . $this->normalizePostalCode($to['post_code'] ?? '') . '</ReceiverPostalCode>
        <TotalWeight>' . max(0.1, $parcel['weight'] ?? 0.1) . '</TotalWeight>
        <WeightUnit>KG</WeightUnit>
        <ServiceID>' . $serviceCode . '</ServiceID>
      </request>
    </GetQuickEstimate>
  </soap:Body>
</soap:Envelope>';
        
        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://purolator.com/ws/estimating/GetQuickEstimate"',
            'Authorization: Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['activation_key'])
        ];
        
        $response = $this->makeRequest($this->apiUrl, $xmlRequest, $headers);
        
        if ($response === false) {
            return null;
        }
        
        // Parse XML response
        $xml = simplexml_load_string($response);
        $xml->registerXPathNamespace('ns', 'http://purolator.com/ws/estimating/');
        
        $totalPrice = $xml->xpath('//ns:TotalPrice');
        $currency = $xml->xpath('//ns:CurrencyCode');
        
        if (empty($totalPrice)) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)(string)$totalPrice[0],
            'currency' => (string)($currency[0] ?? 'CAD'),
            'transit_days' => 0
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'PurolatorExpress' => 'Purolator Express',
            'PurolatorGround' => 'Purolator Ground',
            'PurolatorExpress9AM' => 'Purolator Express 9AM',
            'PurolatorExpress1030AM' => 'Purolator Express 10:30AM',
            'PurolatorExpressEvening' => 'Purolator Express Evening'
        ];
        return $names[$code] ?? $code;
    }
    
    private function makeRequest(string $url, string $body, array $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Purolator API error: HTTP $httpCode - $response");
            return false;
        }
        
        return $response;
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']) && !empty($this->config['activation_key']);
    }
}

/**
 * Canpar API Integration
 * Documentation: https://www.canpar.com/en/api/
 */
class CanparAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://www.canpar.com/api/';
    private $testMode;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://sandbox.canpar.com/api/';
        }
    }
    
    public function getName(): string { return 'Canpar'; }
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
