<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Invoice line item model
 * 
 * @property string $name Item name
 * @property string|null $description Item description
 * @property float $quantity Quantity
 * @property float $price Unit price (without VAT)
 * @property float $vat_rate VAT rate percentage
 * @property string $unit_of_measure Unit of measure
 * @property string|null $sku Stock keeping unit
 * @property string|null $product_id Product CzUid reference
 */
class InvoiceItem extends Model
{
    /**
     * Get item name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name', '');
    }
    
    /**
     * Get quantity
     * 
     * @return float
     */
    public function getQuantity(): float
    {
        return (float) $this->getAttribute('quantity', 1);
    }
    
    /**
     * Get unit price (without VAT)
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
     * Get subtotal (quantity * price, without VAT)
     * 
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->getQuantity() * $this->getPrice();
    }
    
    /**
     * Get VAT amount
     * 
     * @return float
     */
    public function getTax(): float
    {
        return $this->getSubtotal() * ($this->getVatRate() / 100);
    }
    
    /**
     * Get total (subtotal + VAT)
     * 
     * @return float
     */
    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax();
    }
    
    /**
     * Get unit price with VAT
     * 
     * @return float
     */
    public function getPriceWithVat(): float
    {
        return $this->getPrice() * (1 + $this->getVatRate() / 100);
    }
    
    /**
     * Get unit of measure
     * 
     * @return string
     */
    public function getUnitOfMeasure(): string
    {
        return $this->getAttribute('unit_of_measure', 'buc');
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
     * Get product ID reference
     * 
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->getAttribute('product_id');
    }
    
    /**
     * Check if item has discount
     * 
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->getAttribute('discount_percent', 0) > 0 || 
               $this->getAttribute('discount_amount', 0) > 0;
    }
    
    /**
     * Get discount amount
     * 
     * @return float
     */
    public function getDiscountAmount(): float
    {
        $discountPercent = (float) $this->getAttribute('discount_percent', 0);
        $discountAmount = (float) $this->getAttribute('discount_amount', 0);
        
        if ($discountPercent > 0) {
            return $this->getSubtotal() * ($discountPercent / 100);
        }
        
        return $discountAmount;
    }
    
    /**
     * Get formatted display text
     * 
     * @return string
     */
    public function getDisplayText(): string
    {
        $text = $this->getName();
        
        if ($description = $this->getAttribute('description')) {
            $text .= "\n" . $description;
        }
        
        return $text;
    }
}