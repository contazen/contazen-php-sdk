<?php

declare(strict_types=1);

namespace Contazen\Collections;

use Contazen\Models\Product;

/**
 * Collection of Product models
 */
class ProductCollection extends Collection
{
    /**
     * Create Product instance from data
     * 
     * @param array $data
     * @return Product
     */
    protected static function createItem(array $data): Product
    {
        return Product::fromArray($data);
    }
    
    /**
     * Find product by SKU
     * 
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->find(fn(Product $product) => $product->getSku() === $sku);
    }
    
    /**
     * Find product by barcode
     * 
     * @param string $barcode
     * @return Product|null
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return $this->find(function (Product $product) use ($barcode) {
            return $product->getAttribute('barcode') === $barcode;
        });
    }
    
    /**
     * Get active products
     * 
     * @return array
     */
    public function getActive(): array
    {
        return $this->filter(fn(Product $product) => $product->isActive());
    }
    
    /**
     * Get services
     * 
     * @return array
     */
    public function getServices(): array
    {
        return $this->filter(fn(Product $product) => $product->isService());
    }
    
    /**
     * Get physical products
     * 
     * @return array
     */
    public function getPhysicalProducts(): array
    {
        return $this->filter(fn(Product $product) => !$product->isService());
    }
    
    /**
     * Get products in stock
     * 
     * @return array
     */
    public function getInStock(): array
    {
        return $this->filter(fn(Product $product) => $product->isInStock());
    }
    
    /**
     * Get out of stock products
     * 
     * @return array
     */
    public function getOutOfStock(): array
    {
        return $this->filter(function (Product $product) {
            return $product->tracksStock() && !$product->isInStock();
        });
    }
    
    /**
     * Sort by name
     * 
     * @param string $order asc|desc
     * @return array
     */
    public function sortByName(string $order = 'asc'): array
    {
        $items = $this->items;
        
        usort($items, function (Product $a, Product $b) use ($order) {
            $nameA = $a->getName();
            $nameB = $b->getName();
            
            if ($order === 'asc') {
                return strcasecmp($nameA, $nameB);
            }
            
            return strcasecmp($nameB, $nameA);
        });
        
        return $items;
    }
    
    /**
     * Sort by price
     * 
     * @param string $order asc|desc
     * @return array
     */
    public function sortByPrice(string $order = 'asc'): array
    {
        $items = $this->items;
        
        usort($items, function (Product $a, Product $b) use ($order) {
            $priceA = $a->getPrice();
            $priceB = $b->getPrice();
            
            if ($order === 'asc') {
                return $priceA <=> $priceB;
            }
            
            return $priceB <=> $priceA;
        });
        
        return $items;
    }
}