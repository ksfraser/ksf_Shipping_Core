<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * FedEx Canada API Adapter
 * Documentation: https://developer.fedex.com/api/en-us/home.html
 */
class FedEx_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $accessToken;
    private $testMode;
    private $apiUrl = 'https://apis.fedex.com/rate/v1/';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://apis-sandbox.fedex.com/rate/v1/';
        }
    }
    
    public function getName(): string {
        return 'FedEx Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false;
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        // Get OAuth token
        if (empty($this->accessToken)) {
            $this->accessToken = $this->getOAuthToken();
            if (empty($this->accessToken)) {
                return [];
            }
        }
        
        $rates = [];
        $services = [
            'FEDEX_GROUND' => 'FedEx Ground',
            'FEDEX_2_DAY' => 'FedEx 2Day',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Economy',
            'STANDARD_OVERNIGHT' => 'Standard Overnight',
            'PRIORITY_OVERNIGHT' => 'Priority Overnight',
            'FEDEX_FIRST_FREIGHT' => 'FedEx First Freight'
        ];
        
        foreach ($services as $code => $name) {
            $rate = $this->getRateForService($code, $from, $to, $parcel, $options);
            if ($rate !== null) {
                $rates[] = $rate;
            }
        }
        
        return $rates;
    }
    
    private function getOAuthToken(): string {
        $clientId = $this->config['client_id'] ?? '';
        $clientSecret = $this->config['client_secret'] ?? '';
        
        $endpoint = $this->testMode 
            ? 'https://apis-sandbox.fedex.com/oauth/token'
            : 'https://apis.fedex.com/oauth/token';
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("FedEx OAuth error: HTTP $httpCode");
            return '';
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? '';
    }
    
    private function getRateForService(string $serviceCode, array $from, array $to, array $parcel, array $options): ?array {
        $endpoint = $this->apiUrl . 'rates/quotes';
        
        $requestBody = [
            'accountNumber' => [
                'value' => $this->config['account_number'] ?? ''
            ],
            'requestedShipment' => [
                'shipper' => [
                    'address' => [
                        'postalCode' => $this->normalizePostalCode($from['post_code'] ?? ''),
                        'countryCode' => $from['country'] ?? 'CA'
                    ]
                ],
                'recipient' => [
                    'address' => [
                        'postalCode' => $this->normalizePostalCode($to['post_code'] ?? ''),
                        'countryCode' => $to['country'] ?? 'CA'
                    ]
                ],
                'pickupType' => 'REGULAR_PICKUP',
                'serviceType' => $serviceCode,
                'rateRequestType' => ['LIST'],
                'packagingType' => 'YOUR_PACKAGING',
                'totalWeight' => [
                    'value' => max(0.1, $parcel['weight'] ?? 0.1),
                    'units' => 'KG'
                ],
                'items' => [
                    [
                        'weight' => [
                            'value' => max(0.1, $parcel['weight'] ?? 0.1),
                            'units' => 'KG'
                        ],
                        'dimensions' => [
                            'length' => max(1, $parcel['length'] ?? 1),
                            'width' => max(1, $parcel['width'] ?? 1),
                            'height' => max(1, $parcel['height'] ?? 1),
                            'units' => 'CM'
                        ]
                    ]
                ]
            ]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ];
        
        $response = $this->makeRequest($endpoint, $requestBody, $headers);
        
        if ($response === false || !isset($response['output']['rateReplyDetails'][0])) {
            return null;
        }
        
        $rateDetail = $response['output']['rateReplyDetails'][0];
        $netCharge = $rateDetail['ratedShipmentDetails'][0]['totalNetCharge'] ?? null;
        
        if (!$netCharge) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)($netCharge['amount'] ?? 0),
            'currency' => $netCharge['currency'] ?? 'CAD',
            'transit_days' => 0
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'FEDEX_GROUND' => 'FedEx Ground',
            'FEDEX_2_DAY' => 'FedEx 2Day',
            'FEDEX_EXPRESS_SAVER' => 'FedEx Economy',
            'STANDARD_OVERNIGHT' => 'Standard Overnight',
            'PRIORITY_OVERNIGHT' => 'Priority Overnight'
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
            error_log("FedEx API error: HTTP $httpCode - $response");
            return false;
        }
        
        return json_decode($response, true);
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['client_id']) && !empty($this->config['client_secret']);
    }
}
