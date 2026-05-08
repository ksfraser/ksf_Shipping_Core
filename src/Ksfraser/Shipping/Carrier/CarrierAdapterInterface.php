<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Carrier Adapter Interface for Canadian Shipping Companies
 */
interface CarrierAdapterInterface {
    
    /**
     * Get carrier name
     */
    public function getName(): string;
    
    /**
     * Check if carrier supports guest quotes (no auth required)
     */
    public function supportsGuestQuotes(): bool;
    
    /**
     * Get shipping rates
     * 
     * @param array $from Store address
     * @param array $to Customer address  
     * @param array $parcel Weight, dimensions, parcel class
     * @param array $options Additional options (signature, insurance, etc.)
     * @return array Rate quotes with service codes
     */
    public function getRates(array $from, array $to, array $parcel, array $options = []): array;
    
    /**
     * Validate configuration (API keys, credentials, etc.)
     */
    public function validateConfig(): bool;
}
