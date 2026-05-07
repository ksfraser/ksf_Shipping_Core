<?php
namespace Ksf\Shipping;

/**
 * Shipping Method Interface
 */
interface ShippingMethodInterface {
    
    /**
     * Get method ID
     * 
     * @return string
     */
    public function getId(): string;
    
    /**
     * Get method title
     * 
     * @return string
     */
    public function getTitle(): string;
    
    /**
     * Check if method is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool;
    
    /**
     * Check if method is available for destination
     * 
     * @param array $destination ['country', 'state', 'postcode', 'city']
     * @return bool
     */
    public function isAvailableForDestination(array $destination): bool;
    
    /**
     * Calculate shipping rates for packages
     * 
     * @param array $packages
     * @param array $context
     * @return array Rates with ['id', 'label', 'cost', 'taxes', 'package']
     */
    public function calculateShipping(array $packages, array $context): array;
}

/**
 * Flat Rate Shipping Method
 */
class FlatRateMethod implements ShippingMethodInterface {
    
    private $id = 'flat_rate';
    private $title = 'Flat Rate';
    private $enabled = true;
    private $cost = 0;
    private $taxable = false;
    private $allowedDestinations = [];
    
    public function __construct(array $config = []) {
        $this->title = $config['title'] ?? $this->title;
        $this->enabled = $config['enabled'] ?? $this->enabled;
        $this->cost = (float)($config['cost'] ?? $this->cost);
        $this->taxable = $config['taxable'] ?? $this->taxable;
        $this->allowedDestinations = $config['destinations'] ?? [];
    }
    
    public function getId(): string { return $this->id; }
    
    public function getTitle(): string { return $this->title; }
    
    public function isEnabled(): bool { return $this->enabled; }
    
    public function isAvailableForDestination(array $destination): bool {
        if (empty($this->allowedDestinations)) {
            return true; // No restrictions
        }
        
        foreach ($this->allowedDestinations as $allowed) {
            if ($this->matchDestination($destination, $allowed)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function calculateShipping(array $packages, array $context): array {
        $totalCost = 0;
        
        foreach ($packages as $package) {
            $totalCost += $this->cost;
            
            // Add additional costs based on weight if configured
            if (isset($package['weight']) && isset($this->config['cost_per_weight'])) {
                $totalCost += $package['weight'] * $this->config['cost_per_weight'];
            }
        }
        
        return [
            [
                'id' => $this->id . ':' . $this->id,
                'label' => $this->title,
                'cost' => $totalCost,
                'taxes' => $this->taxable ? $this->calculateTaxes($totalCost) : []
            ]
        ];
    }
    
    private function matchDestination(array $destination, array $allowed): bool {
        if (!empty($allowed['country']) && ($destination['country'] ?? '') !== $allowed['country']) {
            return false;
        }
        if (!empty($allowed['state']) && ($destination['state'] ?? '') !== $allowed['state']) {
            return false;
        }
        if (!empty($allowed['postcode'])) {
            $pattern = '/' . str_replace('*', '.*', $allowed['postcode']) . '/';
            if (!preg_match($pattern, $destination['postcode'] ?? '')) {
                return false;
            }
        }
        return true;
    }
    
    private function calculateTaxes(float $cost): array {
        // Implement tax calculation logic
        return [];
    }
}

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
        return true; // Available everywhere if enabled
    }
    
    public function calculateShipping(array $packages, array $context): array {
        $cartTotal = $context['cart_total'] ?? 0;
        
        if ($this->minAmount > 0 && $cartTotal < $this->minAmount) {
            return []; // Not eligible
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
