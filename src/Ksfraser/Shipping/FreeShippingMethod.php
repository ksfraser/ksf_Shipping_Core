<?php
namespace Ksfraser\Shipping;

/**
 * Free Shipping Method
 */
class FreeShippingMethod implements ShippingMethodInterface {
    
    private $id = 'free_shipping';
    private $title = 'Free Shipping';
    private $enabled = true;
    private $minAmount = 0;
    
    public function __construct(array $config = []) {
        $this->title = $config['title'] ?? $this->title;
        $this->enabled = $config['enabled'] ?? $this->enabled;
        $this->minAmount = (float)($config['min_amount'] ?? $this->minAmount);
    }
    
    public function getId(): string { return $this->id; }
    
    public function getTitle(): string { return $this->title; }
    
    public function isEnabled(): bool { return $this->enabled; }
    
    public function isAvailableForDestination(array $destination): bool {
        return true;
    }
    
    public function calculateShipping(array $packages, array $context): array {
        $cartTotal = $context['cart_total'] ?? 0;
        
        if ($this->minAmount > 0 && $cartTotal < $this->minAmount) {
            return [];
        }
        
        return [
            [
                'id' => $this->id . ':' . $this->id,
                'label' => $this->title,
                'cost' => 0,
                'taxes' => []
            ]
        ];
    }
}