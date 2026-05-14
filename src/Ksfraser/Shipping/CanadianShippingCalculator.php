<?php
namespace Ksfraser\Shipping;

use Ksfraser\Shipping\Carrier\CarrierAdapterInterface;

/**
 * Enhanced Shipping Calculator with Canadian Carrier Support
 */
class CanadianShippingCalculator {
    
    /** @var array */
    private $carriers = [];
    
    /** @var array */
    private $storeAddress = [];
    
    /** @var array */
    private $parcelDefaults = [
        'weight' => 0,
        'length' => 0,
        'width' => 0,
        'height' => 0,
        'parcel_class' => 'regular'
    ];
    
    /**
     * Register a carrier adapter
     */
    public function registerCarrier(CarrierAdapterInterface $carrier): void {
        $this->carriers[$carrier->getName()] = $carrier;
    }
    
    /**
     * Set store address for shipping origin
     */
    public function setStoreAddress(array $address): void {
        $this->storeAddress = $address;
    }
    
    /**
     * Get all shipping quotes from all carriers
     * 
     * @param array $customerAddress
     * @param array $parcel Parcel details (weight, dimensions, class)
     * @param array $options Additional options (signature, insurance, etc.)
     * @return array All available rates grouped by carrier
     */
    public function getQuotes(array $customerAddress, array $parcel, array $options = []): array {
        $allQuotes = [];
        
        foreach ($this->carriers as $carrierName => $carrier) {
            if (!$carrier->validateConfig()) {
                continue; // Skip misconfigured carriers
            }
            
            try {
                $rates = $carrier->getRates(
                    $this->storeAddress,
                    $customerAddress,
                    array_merge($this->parcelDefaults, $parcel),
                    $options
                );
                
                if (!empty($rates)) {
                    $allQuotes[$carrierName] = [
                        'carrier' => $carrierName,
                        'supports_guest' => $carrier->supportsGuestQuotes(),
                        'rates' => $rates
                    ];
                }
            } catch (\Exception $e) {
                // Log error but continue with other carriers
                error_log("Error getting quotes from {$carrierName}: " . $e->getMessage());
            }
        }
        
        return $allQuotes;
    }
    
    /**
     * Get cheapest quote across all carriers
     */
    public function getCheapestQuote(array $customerAddress, array $parcel, array $options = []): ?array {
        $quotes = $this->getQuotes($customerAddress, $parcel, $options);
        
        $cheapest = null;
        $lowestRate = PHP_FLOAT_MAX;
        
        foreach ($quotes as $carrierQuotes) {
            foreach ($carrierQuotes['rates'] as $rate) {
                if ($rate['rate'] < $lowestRate) {
                    $lowestRate = $rate['rate'];
                    $cheapest = [
                        'carrier' => $carrierQuotes['carrier'],
                        'service_code' => $rate['service_code'],
                        'service_name' => $rate['service_name'],
                        'rate' => $rate['rate'],
                        'currency' => $rate['currency'],
                        'transit_days' => $rate['transit_days'] ?? 0
                    ];
                }
            }
        }
        
        return $cheapest;
    }
    
    /**
     * Get quotes for a specific carrier
     */
    public function getCarrierQuotes(string $carrierName, array $customerAddress, array $parcel, array $options = []): array {
        if (!isset($this->carriers[$carrierName])) {
            return [];
        }
        
        $carrier = $this->carriers[$carrierName];
        
        if (!$carrier->validateConfig()) {
            return [];
        }
        
        return $carrier->getRates(
            $this->storeAddress,
            $customerAddress,
            array_merge($this->parcelDefaults, $parcel),
            $options
        );
    }
}
