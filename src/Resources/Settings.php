<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Models\Settings as SettingsModel;

/**
 * Settings API resource
 * 
 * Retrieve account settings and configuration
 */
class Settings extends Resource
{
    /**
     * Get account settings and configuration
     * 
     * Returns comprehensive settings including:
     * - Firm details (name, address, contact info)
     * - Invoice series configurations
     * - E-Factura OAuth status
     * - Bank accounts
     * - Subscription information
     * 
     * @return SettingsModel
     */
    public function get(): SettingsModel
    {
        $response = $this->http->get('/settings');
        return SettingsModel::fromArray($response->getData());
    }
    
    /**
     * Get firm details only
     * 
     * @return array
     */
    public function getFirmDetails(): array
    {
        $settings = $this->get();
        return $settings->firm;
    }
    
    /**
     * Get invoice series configurations
     * 
     * @return array
     */
    public function getInvoiceSeries(): array
    {
        $settings = $this->get();
        return $settings->invoice_series;
    }
    
    /**
     * Get primary invoice series for each document type
     * 
     * @return array
     */
    public function getPrimarySeries(): array
    {
        $settings = $this->get();
        return $settings->primary_series;
    }
    
    /**
     * Get E-Factura configuration and status
     * 
     * @return array
     */
    public function getEfacturaStatus(): array
    {
        $settings = $this->get();
        return $settings->efactura;
    }
    
    /**
     * Check if E-Factura is enabled and configured
     * 
     * @return bool
     */
    public function isEfacturaEnabled(): bool
    {
        $efactura = $this->getEfacturaStatus();
        return $efactura['enabled'] && $efactura['oauth_configured'];
    }
    
    /**
     * Get bank accounts
     * 
     * @return array
     */
    public function getBankAccounts(): array
    {
        $settings = $this->get();
        return $settings->bank_accounts;
    }
    
    /**
     * Get primary bank account
     * 
     * @return array|null
     */
    public function getPrimaryBankAccount(): ?array
    {
        $accounts = $this->getBankAccounts();
        
        foreach ($accounts as $account) {
            if ($account['is_primary']) {
                return $account;
            }
        }
        
        // Return first account if no primary is set
        return $accounts[0] ?? null;
    }
    
    /**
     * Get subscription information
     * 
     * @return array
     */
    public function getSubscription(): array
    {
        $settings = $this->get();
        return $settings->subscription;
    }
    
    /**
     * Check if account has active premium subscription
     * 
     * @return bool
     */
    public function hasPremiumSubscription(): bool
    {
        $subscription = $this->getSubscription();
        
        return $subscription['status'] === 'active' && 
               !in_array(strtolower($subscription['plan']), ['free', 'trial']);
    }
    
    /**
     * Get remaining invoice quota for current period
     * 
     * @return int|null Remaining invoices or null if unlimited
     */
    public function getRemainingInvoiceQuota(): ?int
    {
        $subscription = $this->getSubscription();
        
        if (!isset($subscription['invoices_limit']) || $subscription['invoices_limit'] === null) {
            return null; // Unlimited
        }
        
        $limit = $subscription['invoices_limit'];
        $used = $subscription['invoices_used'] ?? 0;
        
        return max(0, $limit - $used);
    }
    
    /**
     * Update firm settings
     * Note: This endpoint may not be available in the current API
     * 
     * @param array $data Settings to update
     * @return SettingsModel
     */
    public function update(array $data): SettingsModel
    {
        $response = $this->http->patch('/settings', $data);
        return SettingsModel::fromArray($response->getData());
    }
    
    /**
     * Get available currencies
     * 
     * @return array
     */
    public function getCurrencies(): array
    {
        $response = $this->http->get('/settings/currencies');
        return $response->getData();
    }
    
    /**
     * Get available units of measure
     * 
     * @return array
     */
    public function getUnitsOfMeasure(): array
    {
        $response = $this->http->get('/settings/units');
        return $response->getData();
    }
    
    /**
     * Get available VAT rates
     * 
     * @return array
     */
    public function getVatRates(): array
    {
        // Romanian VAT rates
        return [
            ['rate' => 0, 'name' => 'Scutit'],
            ['rate' => 5, 'name' => '5%'],
            ['rate' => 9, 'name' => '9%'],
            ['rate' => 19, 'name' => '19% (Standard)'],
        ];
    }
    
    /**
     * Get document types
     * 
     * @return array
     */
    public function getDocumentTypes(): array
    {
        return [
            ['type' => 'fiscal', 'name' => 'Factură fiscală'],
            ['type' => 'proforma', 'name' => 'Factură proformă'],
            ['type' => 'receipt', 'name' => 'Chitanță'],
            ['type' => 'credit_note', 'name' => 'Notă de credit'],
        ];
    }
    
    /**
     * Get payment methods
     * 
     * @return array
     */
    public function getPaymentMethods(): array
    {
        return [
            ['id' => 'cash', 'name' => 'Numerar'],
            ['id' => 'bank_transfer', 'name' => 'Transfer bancar'],
            ['id' => 'card', 'name' => 'Card'],
            ['id' => 'check', 'name' => 'CEC'],
            ['id' => 'promissory_note', 'name' => 'Bilet la ordin'],
            ['id' => 'compensation', 'name' => 'Compensare'],
            ['id' => 'other', 'name' => 'Altele'],
        ];
    }
}