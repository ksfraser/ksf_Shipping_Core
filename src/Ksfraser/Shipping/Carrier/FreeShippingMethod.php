<?php
namespace Ksfraser\Shipping\Carrier;

/**
 * Free Shipping Method
 * 
 * Provides free shipping when conditions are met
 */
class FreeShippingMethod implements CarrierAdapterInterface {
    
    private string $name;
    private float $minOrderAmount;
    private bool $enabled = true;
    
    public function __construct(array $config = []) {
        $this->name = $config['name'] ?? 'Free Shipping';
        $this->minOrderAmount = (float)($config['min_order_amount'] ?? 0);
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
        
        $orderAmount = $options['order_amount'] ?? 0;
        
        if ($orderAmount < $this->minOrderAmount) {
            return [];
        }
        
        return [
            [
                'service_code' => 'free_shipping',
                'service_name' => $this->name,
                'rate' => 0.00,
                'currency' => 'CAD',
                'transit_days' => 7
            ]
        ];
    }
    
    public function validateConfig(): bool {
        return $this->enabled;
    }
}