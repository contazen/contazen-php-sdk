<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Settings model for firm configuration
 * 
 * @property int $firm_id Firm ID
 * @property string $company_name Company name
 * @property string $cui Company identification number
 * @property string|null $trade_register Trade register number
 * @property string $address Address
 * @property string $city City
 * @property string $county County/State
 * @property string $country Country code
 * @property string|null $bank_account Bank account (IBAN)
 * @property string|null $bank_name Bank name
 * @property string|null $email Contact email
 * @property string|null $phone Phone number
 * @property string|null $website Website URL
 * @property array $invoice_settings Invoice specific settings
 * @property array $efactura_settings E-Factura settings
 * @property bool $vat_payer VAT payer status
 * @property float $default_vat_rate Default VAT rate
 */
class Settings extends Model
{
    /**
     * Get company name
     * 
     * @return string
     */
    public function getCompanyName(): string
    {
        $firm = $this->getAttribute('firm', []);
        return $firm['name'] ?? $firm['legal_name'] ?? $this->getAttribute('company_name', '');
    }
    
    /**
     * Get CUI (Company Identification Number)
     * 
     * @return string
     */
    public function getCui(): string
    {
        $firm = $this->getAttribute('firm', []);
        return $firm['cui'] ?? $this->getAttribute('cui', '');
    }
    
    /**
     * Get formatted CUI with RO prefix if VAT payer
     * 
     * @return string
     */
    public function getFormattedCui(): string
    {
        $cui = $this->getCui();
        
        if ($this->isVatPayer() && !str_starts_with(strtoupper($cui), 'RO')) {
            return 'RO' . $cui;
        }
        
        return $cui;
    }
    
    /**
     * Get trade register number
     * 
     * @return string|null
     */
    public function getTradeRegister(): ?string
    {
        $firm = $this->getAttribute('firm', []);
        return $firm['rc'] ?? $firm['trade_register'] ?? $this->getAttribute('trade_register');
    }
    
    /**
     * Get full address
     * 
     * @return string
     */
    public function getFullAddress(): string
    {
        $firm = $this->getAttribute('firm', []);
        
        $parts = array_filter([
            $firm['address'] ?? $this->getAttribute('address'),
            $firm['city'] ?? $this->getAttribute('city'),
            $firm['county'] ?? $this->getAttribute('county'),
            $firm['country'] ?? $this->getAttribute('country')
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Get bank account (IBAN)
     * 
     * @return string|null
     */
    public function getBankAccount(): ?string
    {
        return $this->getAttribute('bank_account');
    }
    
    /**
     * Get bank name
     * 
     * @return string|null
     */
    public function getBankName(): ?string
    {
        return $this->getAttribute('bank_name');
    }
    
    /**
     * Check if VAT payer
     * 
     * @return bool
     */
    public function isVatPayer(): bool
    {
        return (bool) $this->getAttribute('vat_payer', false);
    }
    
    /**
     * Get default VAT rate
     * 
     * @return float
     */
    public function getDefaultVatRate(): float
    {
        return (float) $this->getAttribute('default_vat_rate', 19);
    }
    
    /**
     * Get invoice settings
     * 
     * @return array
     */
    public function getInvoiceSettings(): array
    {
        return $this->getAttribute('invoice_settings', []);
    }
    
    /**
     * Get invoice setting by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getInvoiceSetting(string $key, $default = null)
    {
        $settings = $this->getInvoiceSettings();
        return $settings[$key] ?? $default;
    }
    
    /**
     * Get E-Factura settings
     * 
     * @return array
     */
    public function getEfacturaSettings(): array
    {
        return $this->getAttribute('efactura', $this->getAttribute('efactura_settings', []));
    }
    
    /**
     * Check if E-Factura is enabled
     * 
     * @return bool
     */
    public function isEfacturaEnabled(): bool
    {
        $settings = $this->getEfacturaSettings();
        return (bool) ($settings['enabled'] ?? false);
    }
    
    /**
     * Get E-Factura environment (test/production)
     * 
     * @return string
     */
    public function getEfacturaEnvironment(): string
    {
        $settings = $this->getEfacturaSettings();
        return $settings['environment'] ?? 'test';
    }
    
    /**
     * Get invoice prefix
     * 
     * @return string
     */
    public function getInvoicePrefix(): string
    {
        return $this->getInvoiceSetting('prefix', 'INV');
    }
    
    /**
     * Get next invoice number
     * 
     * @return int
     */
    public function getNextInvoiceNumber(): int
    {
        return (int) $this->getInvoiceSetting('next_number', 1);
    }
    
    /**
     * Check if auto numbering is enabled
     * 
     * @return bool
     */
    public function isAutoNumberingEnabled(): bool
    {
        return (bool) $this->getInvoiceSetting('auto_numbering', true);
    }
    
    /**
     * Get payment terms in days
     * 
     * @return int
     */
    public function getPaymentTermsDays(): int
    {
        return (int) $this->getInvoiceSetting('payment_terms_days', 30);
    }
    
    /**
     * Get logo URL
     * 
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {
        return $this->getAttribute('logo_url');
    }
    
    /**
     * Get contact email
     * 
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getAttribute('email');
    }
    
    /**
     * Get phone number
     * 
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->getAttribute('phone');
    }
    
    /**
     * Get website URL
     * 
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->getAttribute('website');
    }
    
    /**
     * Get currency code
     * 
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getAttribute('currency', 'RON');
    }
    
    /**
     * Check if split payment (TVA la incasare) is enabled
     * 
     * @return bool
     */
    public function isSplitPaymentEnabled(): bool
    {
        return (bool) $this->getInvoiceSetting('split_payment', false);
    }
    
    /**
     * Get firm data
     * 
     * @return array
     */
    public function getFirm(): array
    {
        return $this->getAttribute('firm', []);
    }
    
    /**
     * Get invoice series list
     * 
     * @return array
     */
    public function getInvoiceSeries(): array
    {
        return $this->getAttribute('invoice_series', []);
    }
    
    /**
     * Get primary series
     * 
     * @return array
     */
    public function getPrimarySeries(): array
    {
        return $this->getAttribute('primary_series', []);
    }
    
    /**
     * Get bank accounts
     * 
     * @return array
     */
    public function getBankAccounts(): array
    {
        return $this->getAttribute('bank_accounts', []);
    }
    
    /**
     * Get subscription info
     * 
     * @return array
     */
    public function getSubscription(): array
    {
        return $this->getAttribute('subscription', []);
    }
}