<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Purolator API Adapter
 * Documentation: https://docs.purolator.com/
 */
class PurolatorAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $testMode;
    private $apiUrl = 'https://webservices.purolator.com/Estimating/EstimatingService.asmx';
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? false;
        
        if ($this->testMode) {
            $this->apiUrl = 'https://devwebservices.purolator.com/Estimating/EstimatingService.asmx';
        }
    }
    
    public function getName(): string {
        return 'Purolator';
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
