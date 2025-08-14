<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Invoice series model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $name Series name
 * @property string $prefix Series prefix (e.g., 'INV', 'FCT')
 * @property int $next_number Next number in sequence
 * @property bool $is_default Default series flag
 * @property string $type Document type (invoice, receipt, proforma)
 * @property bool $is_active Active status
 * @property string|null $description Description
 * @property array|null $settings Series-specific settings
 * @property string $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class Series extends Model
{
    const TYPE_INVOICE = 'invoice';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_PROFORMA = 'proforma';
    const TYPE_CREDIT_NOTE = 'credit_note';
    
    /**
     * Get series name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name', '');
    }
    
    /**
     * Get series prefix
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->getAttribute('prefix', '');
    }
    
    /**
     * Get next number
     * 
     * @return int
     */
    public function getNextNumber(): int
    {
        return (int) $this->getAttribute('next_number', 1);
    }
    
    /**
     * Format document number with this series
     * 
     * @param int|null $number Optional specific number
     * @return string
     */
    public function formatNumber(?int $number = null): string
    {
        $number = $number ?? $this->getNextNumber();
        $settings = $this->getAttribute('settings', []);
        
        // Check for custom formatting
        if (isset($settings['number_format'])) {
            return sprintf($settings['number_format'], $this->getPrefix(), $number);
        }
        
        // Default format with zero padding
        $padding = $settings['number_padding'] ?? 6;
        return $this->getPrefix() . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if this is the default series
     * 
     * @return bool
     */
    public function isDefault(): bool
    {
        return (bool) $this->getAttribute('is_default', false);
    }
    
    /**
     * Check if series is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active', true);
    }
    
    /**
     * Get document type
     * 
     * @return string
     */
    public function getType(): string
    {
        return $this->getAttribute('type', self::TYPE_INVOICE);
    }
    
    /**
     * Check if this is an invoice series
     * 
     * @return bool
     */
    public function isInvoiceSeries(): bool
    {
        return $this->getType() === self::TYPE_INVOICE;
    }
    
    /**
     * Check if this is a receipt series
     * 
     * @return bool
     */
    public function isReceiptSeries(): bool
    {
        return $this->getType() === self::TYPE_RECEIPT;
    }
    
    /**
     * Check if this is a proforma series
     * 
     * @return bool
     */
    public function isProformaSeries(): bool
    {
        return $this->getType() === self::TYPE_PROFORMA;
    }
    
    /**
     * Check if this is a credit note series
     * 
     * @return bool
     */
    public function isCreditNoteSeries(): bool
    {
        return $this->getType() === self::TYPE_CREDIT_NOTE;
    }
    
    /**
     * Get description
     * 
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getAttribute('description');
    }
    
    /**
     * Get series settings
     * 
     * @return array
     */
    public function getSettings(): array
    {
        return $this->getAttribute('settings', []);
    }
    
    /**
     * Get specific setting
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }
    
    /**
     * Check if series requires E-Factura
     * 
     * @return bool
     */
    public function requiresEfactura(): bool
    {
        return (bool) $this->getSetting('requires_efactura', false);
    }
    
    /**
     * Get year reset setting
     * 
     * @return bool
     */
    public function resetsYearly(): bool
    {
        return (bool) $this->getSetting('reset_yearly', false);
    }
    
    /**
     * Get display label
     * 
     * @return string
     */
    public function getDisplayLabel(): string
    {
        $label = $this->getName();
        
        if ($this->isDefault()) {
            $label .= ' (Default)';
        }
        
        if (!$this->isActive()) {
            $label .= ' [Inactive]';
        }
        
        return $label;
    }
}