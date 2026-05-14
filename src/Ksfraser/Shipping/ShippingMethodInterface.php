<?php
namespace Ksfraser\Shipping;

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