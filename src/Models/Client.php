<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Client model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $name Client name
 * @property string|null $email Email address
 * @property string|null $phone Phone number
 * @property string|null $cui Romanian tax ID (CUI/CIF)
 * @property string|null $cui_prefix CUI prefix (RO for VAT registered)
 * @property string|null $rc Trade registry number
 * @property string $client_type Client type (b2b, b2c)
 * @property string|null $address Street address
 * @property string|null $city City
 * @property string|null $county County/State
 * @property string|null $country Country code
 * @property string|null $postal_code Postal code
 * @property string|null $iban Bank account IBAN
 * @property string|null $bank Bank name
 * @property string|null $contact_person Contact person name
 * @property \Carbon\Carbon $created_at Creation date
 * @property \Carbon\Carbon|null $updated_at Last update date
 * @property array $metadata Additional metadata
 */
class Client extends Model
{
    /**
     * Client types
     */
    public const TYPE_B2B = 'b2b';
    public const TYPE_B2C = 'b2c';
    
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
     * Get client name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->getAttribute('name', '');
    }
    
    /**
     * Get client type
     * 
     * @return string
     */
    public function getClientType(): string
    {
        return $this->getAttribute('client_type', self::TYPE_B2C);
    }
    
    /**
     * Check if client is B2B
     * 
     * @return bool
     */
    public function isB2B(): bool
    {
        return $this->getClientType() === self::TYPE_B2B;
    }
    
    /**
     * Check if client is B2C
     * 
     * @return bool
     */
    public function isB2C(): bool
    {
        return $this->getClientType() === self::TYPE_B2C;
    }
    
    /**
     * Get full CUI with prefix
     * 
     * @return string|null
     */
    public function getFullCui(): ?string
    {
        $cui = $this->getAttribute('cui');
        if (!$cui) {
            return null;
        }
        
        $prefix = $this->getAttribute('cui_prefix');
        if ($prefix) {
            return $prefix . $cui;
        }
        
        return $cui;
    }
    
    /**
     * Check if client has VAT registration
     * 
     * @return bool
     */
    public function hasVatRegistration(): bool
    {
        return !empty($this->getAttribute('cui_prefix'));
    }
    
    /**
     * Get formatted address
     * 
     * @return string
     */
    public function getFormattedAddress(): string
    {
        $parts = [];
        
        if ($address = $this->getAttribute('address')) {
            $parts[] = $address;
        }
        
        if ($city = $this->getAttribute('city')) {
            $parts[] = $city;
        }
        
        if ($county = $this->getAttribute('county')) {
            $parts[] = $county;
        }
        
        if ($postalCode = $this->getAttribute('postal_code')) {
            $parts[] = $postalCode;
        }
        
        if ($country = $this->getAttribute('country')) {
            if ($country !== 'RO') {
                $parts[] = $country;
            }
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Get invoice count
     * 
     * @return int
     */
    public function getInvoiceCount(): int
    {
        $metadata = $this->getAttribute('metadata', []);
        return $metadata['invoices_count'] ?? 0;
    }
    
    /**
     * Get unpaid total
     * 
     * @return float
     */
    public function getUnpaidTotal(): float
    {
        $metadata = $this->getAttribute('metadata', []);
        return (float) ($metadata['unpaid_total'] ?? 0);
    }
    
    /**
     * Check if client can be deleted
     * 
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        return $this->getInvoiceCount() === 0;
    }
    
    /**
     * Convert to invoice client format
     * 
     * @return array
     */
    public function toInvoiceFormat(): array
    {
        return [
            'cz_uid' => $this->getCzUid(),
            'name' => $this->getName(),
            'email' => $this->getAttribute('email'),
            'phone' => $this->getAttribute('phone'),
            'cui' => $this->getAttribute('cui'),
            'cui_prefix' => $this->getAttribute('cui_prefix'),
            'rc' => $this->getAttribute('rc'),
            'address' => $this->getAttribute('address'),
            'city' => $this->getAttribute('city'),
            'county' => $this->getAttribute('county'),
            'country' => $this->getAttribute('country', 'RO'),
            'postal_code' => $this->getAttribute('postal_code'),
            'iban' => $this->getAttribute('iban'),
            'bank' => $this->getAttribute('bank'),
        ];
    }
}