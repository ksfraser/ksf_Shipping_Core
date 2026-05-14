<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Flat Rate Shipping Method
 * 
 * Simple carrier that charges a flat rate regardless of weight/distance
 */
class FlatRateMethod implements CarrierAdapterInterface {
    
    private string $name;
    private float $rate;
    private bool $enabled = true;
    
    public function __construct(array $config = []) {
        $this->name = $config['name'] ?? 'Flat Rate';
        $this->rate = (float)($config['rate'] ?? 9.99);
        $this->enabled = (bool)($config['enabled'] ?? true);
    }
    
    public function getName(): string {
        return $this->name;
    }
    
    public function supportsGuestQuotes(): bool {
        return true;
    }
    
    public function getRates(array $from, array $to, array $parcel, array $options = []): array {
        if (!$this->enabled) {
            return [];
        }
        
        return [
            [
                'service_code' => 'flat_rate',
                'service_name' => $this->name,
                'rate' => $this->rate,
                'currency' => 'CAD',
                'transit_days' => 5
            ]
        ];
    }
    
    public function validateConfig(): bool {
        return $this->enabled && $this->rate >= 0;
    }
}