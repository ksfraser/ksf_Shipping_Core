<?php
namespace Ksf\Shipping;

/**
 * Framework-agnostic Shipping Rate Calculator
 * Extracted from WooCommerce Shipping methods core logic
 */
class ShippingCalculator {
    
    /** @var array */
    private $methods = [];
    
    /** @var array */
    private $packages = [];
    
    /**
     * Register a shipping method
     * 
     * @param ShippingMethodInterface $method
     */
    public function registerMethod(ShippingMethodInterface $method): void {
        $this->methods[$method->getId()] = $method;
    }
    
    /**
     * Add a package for shipping calculation
     * 
     * @param array $package ['weight'=>float, 'dimensions'=>[l,w,h], 'items'=>array, 'destination'=>array]
     */
    public function addPackage(array $package): void {
        $this->packages[] = $package;
    }
    
    /**
     * Calculate shipping rates for all packages
     * 
     * @param array $context ['destination'=>['country','state','postcode','city'], 'user_id'=>int]
     * @return array Available shipping rates grouped by method
     */
    public function calculateRates(array $context = []): array {
        $availableRates = [];
        
        foreach ($this->methods as $method) {
            if (!$method->isEnabled()) {
                continue;
            }
            
            if (!$method->isAvailableForDestination($context['destination'] ?? [])) {
                continue;
            }
            
            $rates = $method->calculateShipping($this->packages, $context);
            
            foreach ($rates as $rate) {
                $availableRates[] = [
                    'method_id' => $method->getId(),
                    'method_title' => $method->getTitle(),
                    'rate_id' => $rate['id'],
                    'title' => $rate['label'],
                    'cost' => (float)$rate['cost'],
                    'taxes' => $rate['taxes'] ?? [],
                    'package' => $rate['package'] ?? null
                ];
            }
        }
        
        // Sort by cost (lowest first)
        usort($availableRates, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });
        
        return $availableRates;
    }
    
    /**
     * Get the cheapest available rate
     * 
     * @param array $context
     * @return array|null
     */
    public function getCheapestRate(array $context = []): ?array {
        $rates = $this->calculateRates($context);
        return $rates[0] ?? null;
    }
    
    /**
     * Clear packages (for new calculation)
     */
    public function clearPackages(): void {
        $this->packages = [];
    }
}
