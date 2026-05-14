<?php
namespace Ksfraser\Shipping;

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
            return true;
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
        return [];
    }
}