<?php
namespace Ksf\Shipping;

/**
 * Canadian Carrier API Adapter Interface
 */
interface CarrierAdapterInterface {
    
    /**
     * Get carrier name
     */
    public function getName(): string;
    
    /**
     * Check if carrier supports guest quotes
     */
    public function supportsGuestQuotes(): bool;
    
    /**
     * Get shipping rates
     * 
     * @param array $from Store address
     * @param array $to Customer address  
     * @param array $parcel Weight, dimensions, parcel class
     * @param array $options Additional options
     * @return array Rate quotes with service codes
     */
    public function getRates(array $from, array $to, array $parcel, array $options = []): array;
    
    /**
     * Validate configuration
     */
    public function validateConfig(): bool;
}

/**
 * Canada Post Adapter
 */
class CanadaPostAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://soa-gw.canadapost.ca/rs/';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'Canada Post';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires API credentials
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // Canada Post API requires:
        // - customerNumber (from config)
        // - API Key (from config)
        // - Origin postal code, Destination postal code
        // - Weight (kg), Dimensions (cm)
        // - Parcel class (regular, expedited, xpresspost, priority)
        
        $rates = [];
        
        // Build request based on Canada Post API
        $services = ['DOM.EP', 'DOM.XP', 'DOM.PC', 'DOM.RP']; // Expedited, Xpresspost, Priority, Regular
        
        foreach ($services as $serviceCode) {
            // API call to Canada Post
            // This is a placeholder - actual implementation would use cURL/Guzzle
            $rates[] = [
                'service_code' => $serviceCode,
                'service_name' => $this->getServiceName($serviceCode),
                'rate' => 0, // Will be populated from API
                'currency' => 'CAD',
                'transit_days' => 0
            ];
        }
        
        return $rates;
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']) && !empty($this->config['customer_number']);
    }
    
    private function getServiceName(string $code): string {
        $names = [
            'DOM.EP' => 'Expedited Parcel',
            'DOM.XP' => 'Xpresspost',
            'DOM.PC' => 'Priority',
            'DOM.RP' => 'Regular Parcel'
        ];
        return $names[$code] ?? $code;
    }
}

/**
 * UPS Canada Adapter
 */
class UPS_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://onlinetools.ups.com/api/rating/v1/';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'UPS Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires OAuth token
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // UPS API requires:
        // - OAuth Token (from config)
        // - Shipper number (from config)
        // - From/To postal codes
        // - Weight, Dimensions
        // - Service codes (01=Next Day, 02=2nd Day, 03=Ground, etc.)
        
        $rates = [];
        
        // Placeholder for UPS API integration
        return $rates;
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['oauth_token']) || (!empty($this->config['user_id']) && !empty($this->config['password']));
    }
}

/**
 * FedEx Canada Adapter
 */
class FedEx_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://apis.fedex.com/rate/v1/';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'FedEx Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires API credentials
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // FedEx API requires:
        // - Client ID + Client Secret (OAuth)
        // - From/To postal codes
        // - Weight, Dimensions
        // - Service types (PRIORITY_OVERNIGHT, FEDEX_GROUND, etc.)
        
        $rates = [];
        
        // Placeholder for FedEx API integration
        return $rates;
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['client_id']) && !empty($this->config['client_secret']);
    }
}

/**
 * DHL Canada Adapter
 */
class DHL_CanadaAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://api-eu.dhl.com/parcel/de/shipping/v2/rates';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'DHL Canada';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires API key
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // DHL API integration placeholder
        return [];
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']);
    }
}

/**
 * Purolator Adapter
 */
class PurolatorAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://webservices.purolator.com/Estimating/EstimatingService.asmx';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'Purolator';
    }
    
    public function supportsGuestQuotes(): bool {
        return false; // Requires API key + activation key
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // Purolator API requires:
        // - API Key + Activation Key
        // - From/To postal codes  
        // - Weight, Dimensions
        // - Service types (PurolatorExpress, PurolatorGround, etc.)
        
        $rates = [];
        
        // Placeholder for Purolator API integration
        return $rates;
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']) && !empty($this->config['activation_key']);
    }
}

/**
 * Canpar Adapter
 */
class CanparAdapter implements CarrierAdapterInterface {
    
    private $config;
    private $apiUrl = 'https://www.canpar.com/';
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function getName(): string {
        return 'Canpar';
    }
    
    public function supportsGuestQuotes(): bool {
        return true; // May support guest quotes
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        // Canpar API integration placeholder
        return [];
    }
    
    public function validateConfig(): bool {
        return !empty($this->config['api_key']) || (!empty($this->config['username']) && !empty($this->config['password']));
    }
}
