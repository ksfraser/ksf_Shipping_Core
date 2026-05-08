<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * UPS Canada API Adapter
 * Documentation: https://developer.ups.com/api/rating
 */
class UPS_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $oauthToken;
    private $testMode;
    private $apiUrl = 'https://onlinetools.ups.com/api/rating/v1/';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://wwwcie.ups.com/api/rating/v1/';
        }
    }
    
    public function getName(): string {
        return 'UPS Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false;
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->validateConfig()) {
            return [];
        }
        
        // Get OAuth token if not cached
        if (empty($this->oauthToken)) {
            $this->oauthToken = $this->getOAuthToken();
            if (empty($this->oauthToken)) {
                return [];
            }
        }
        
        $rates = [];
        $services = [
            '01' => 'Next Day Air',
            '02' => '2nd Day Air', 
            '03' => 'Ground',
            '12' => '3 Day Select',
            '13' => 'Next Day Air Saver',
            '14' => 'Next Day Air Early'
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
        
        if (empty($clientId) || empty($clientSecret)) {
            $userId = $this->config['user_id'] ?? '';
            $password = $this->config['password'] ?? '';
            if (empty($userId) || empty($password)) {
                return '';
            }
        }
        
        $endpoint = $this->testMode 
            ? 'https://wwwcie.ups.com/security/v1/oauth/token'
            : 'https://onlinetools.ups.com/security/v1/oauth/token';
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode(($clientId ?? $userId) . ':' . ($clientSecret ?? $password))
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("UPS OAuth error: HTTP $httpCode");
            return '';
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? '';
    }
    
    private function getRateForService(string $serviceCode, array $from, array $to, array $parcel, array $options): ?array {
        $endpoint = $this->apiUrl . 'shop';
        
        $requestBody = [
            'RateRequest' => [
                'Request' => [
                    'RequestOption' => 'Shop',
                    'TransactionReference' => [
                        'CustomerContext' => 'FA Shipping Quote'
                    ]
                ],
                'Shipment' => [
                    'Shipper' => [
                        'Name' => $from['company'] ?? 'Store',
                        'ShipperNumber' => $this->config['shipper_number'] ?? '',
                        'Address' => [
                            'AddressLine' => [$from['address'] ?? ''],
                            'City' => $from['city'] ?? '',
                            'StateProvinceCode' => $from['state'] ?? '',
                            'PostalCode' => $this->normalizePostalCode($from['post_code'] ?? ''),
                            'CountryCode' => $from['country'] ?? 'CA'
                        ]
                    ],
                    'ShipTo' => [
                        'Name' => $to['company'] ?? 'Customer',
                        'Address' => [
                            'AddressLine' => [$to['address'] ?? ''],
                            'City' => $to['city'] ?? '',
                            'StateProvinceCode' => $to['state'] ?? '',
                            'PostalCode' => $this->normalizePostalCode($to['post_code'] ?? ''),
                            'CountryCode' => $to['country'] ?? 'CA'
                        ]
                    ],
                    'Package' => [
                        [
                            'PackagingType' => ['Code' => '02'],
                            'Dimensions' => [
                                'UnitOfMeasurement' => ['Code' => 'CM'],
                                'Length' => (string)($parcel['length'] ?? 10),
                                'Width' => (string)($parcel['width'] ?? 10),
                                'Height' => (string)($parcel['height'] ?? 10)
                            ],
                            'PackageWeight' => [
                                'UnitOfMeasurement' => ['Code' => 'KGS'],
                                'Weight' => (string)max(0.1, $parcel['weight'] ?? 0.1)
                            ]
                        ]
                    ],
                    'Service' => ['Code' => $serviceCode]
                ]
            ]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->oauthToken,
            'transId: ' . uniqid(),
            'transactionSrc: FA_Shipping'
        ];
        
        $response = $this->makeRequest($endpoint, $requestBody, $headers);
        
        if ($response === false || !isset($response['RateResponse']['RatedShipment'])) {
            return null;
        }
        
        $shipment = $response['RateResponse']['RatedShipment'][0] ?? null;
        if (!$shipment) {
            return null;
        }
        
        return [
            'service_code' => $serviceCode,
            'service_name' => $this->getServiceName($serviceCode),
            'rate' => (float)($shipment['TotalCharges']['MonetaryValue'] ?? 0),
            'currency' => $shipment['TotalCharges']['CurrencyCode'] ?? 'CAD',
            'transit_days' => (int)($shipment['GuaranteedDaysToDelivery'] ?? 0)
        ];
    }
    
    private function normalizePostalCode(string $postalCode): string {
        return strtoupper(str_replace(' ', '', $postalCode));
    }
    
    private function getServiceName(string $code): string {
        $names = [
            '01' => 'Next Day Air',
            '02' => '2nd Day Air',
            '03' => 'Ground',
            '12' => '3 Day Select',
            '13' => 'Next Day Air Saver',
            '14' => 'Next Day Air Early'
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
            error_log("UPS API error: HTTP $httpCode - $response");
            return false;
        }
        
        return json_decode($response, true);
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['client_id']) || !empty($this->config['user_id']);
    }
}
