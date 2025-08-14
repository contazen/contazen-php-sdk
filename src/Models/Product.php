<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Product model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $name Product name
 * @property string|null $description Product description
 * @property string $currency Currency code
 * @property float $price Product price
 * @property float $vat_rate VAT rate percentage
 * @property string $unit_of_measure Unit of measure
 * @property string|null $sku Stock keeping unit
 * @property string|null $barcode Barcode
 * @property string|null $category Category name
 * @property bool $is_active Active status
 * @property bool $is_service Is service (not physical product)
 * @property bool $track_stock Track stock levels
 * @property int|null $stock_quantity Current stock quantity
 * @property string|null $ubl_um UBL unit of measure code
 * @property string|null $ubl_nc UBL nomenclature code
 * @property string|null $ubl_cpv UBL CPV code
 * @property \Carbon\Carbon $created_at Creation date
 * @property \Carbon\Carbon|null $modified_at Last modification date
 */
class Product extends Model
{
    /**
     * Get CzUid
     * 
     * @return string|null
     */
    public function getCzUid(): ?string
    {
        return $this->getAttribute('cz_uid') ?? $this->getAttribute('id');
    }
    
    /**
     * Get product name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name', '');
    }
    
    /**
     * Get SKU
     * 
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->getAttribute('sku');
    }
    
    /**
     * Get price
     * 
     * @return float
     */
    public function getPrice(): float
    {
        return (float) $this->getAttribute('price', 0);
    }
    
    /**
     * Get VAT rate
     * 
     * @return float
     */
    public function getVatRate(): float
    {
        return (float) $this->getAttribute('vat_rate', 19);
    }
    
    /**
     * Get price with VAT
     * 
     * @return float
     */
    public function getPriceWithVat(): float
    {
        $price = $this->getPrice();
        $vatRate = $this->getVatRate();
        
        return $price * (1 + $vatRate / 100);
    }
    
    /**
     * Get VAT amount
     * 
     * @return float
     */
    public function getVatAmount(): float
    {
        $price = $this->getPrice();
        $vatRate = $this->getVatRate();
        
        return $price * ($vatRate / 100);
    }
    
    /**
     * Get formatted price
     * 
     * @param bool $withVat Include VAT
     * @return string
     */
    public function getFormattedPrice(bool $withVat = false): string
    {
        $price = $withVat ? $this->getPriceWithVat() : $this->getPrice();
        $currency = $this->getAttribute('currency', 'RON');
        
        return number_format($price, 2) . ' ' . $currency;
    }
    
    /**
     * Check if product is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active', true);
    }
    
    /**
     * Check if product is a service
     * 
     * @return bool
     */
    public function isService(): bool
    {
        return (bool) $this->getAttribute('is_service', false);
    }
    
    /**
     * Check if product tracks stock
     * 
     * @return bool
     */
    public function tracksStock(): bool
    {
        return (bool) $this->getAttribute('track_stock', false);
    }
    
    /**
     * Get stock quantity
     * 
     * @return int|null
     */
    public function getStockQuantity(): ?int
    {
        if (!$this->tracksStock()) {
            return null;
        }
        
        return (int) $this->getAttribute('stock_quantity', 0);
    }
    
    /**
     * Check if product is in stock
     * 
     * @return bool
     */
    public function isInStock(): bool
    {
        if (!$this->tracksStock()) {
            return true; // Services and non-tracked products are always "in stock"
        }
        
        return $this->getStockQuantity() > 0;
    }
    
    /**
     * Check if sufficient stock is available
     * 
     * @param int $quantity Required quantity
     * @return bool
     */
    public function hasStock(int $quantity): bool
    {
        if (!$this->tracksStock()) {
            return true;
        }
        
        return $this->getStockQuantity() >= $quantity;
    }
    
    /**
     * Convert to invoice item format
     * 
     * @param int $quantity Quantity
     * @return array
     */
    public function toInvoiceItem(int $quantity = 1): array
    {
        return [
            'product_id' => $this->getCzUid(),
            'name' => $this->getName(),
            'description' => $this->getAttribute('description'),
            'quantity' => $quantity,
            'price' => $this->getPrice(),
            'vat_rate' => $this->getVatRate(),
            'unit_of_measure' => $this->getAttribute('unit_of_measure', 'buc'),
            'sku' => $this->getSku(),
            'ubl_um' => $this->getAttribute('ubl_um'),
            'ubl_nc' => $this->getAttribute('ubl_nc'),
            'ubl_cpv' => $this->getAttribute('ubl_cpv'),
        ];
    }
}