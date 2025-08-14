<?php

declare(strict_types=1);

namespace Contazen\Validators;

/**
 * Romanian CUI (Cod Unic de Inregistrare) validator
 */
class CuiValidator
{
    /**
     * Validate Romanian CUI
     * 
     * @param string $cui
     * @return bool
     */
    public static function validate(string $cui): bool
    {
        // Remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        // Remove any non-digit characters
        $cui = preg_replace('/[^0-9]/', '', $cui);
        
        // CUI must be between 2 and 10 digits
        if (!preg_match('/^[0-9]{2,10}$/', $cui)) {
            return false;
        }
        
        // Convert to integer for validation
        $cuiInt = (int) $cui;
        
        // CUI must be at least 10
        if ($cuiInt < 10) {
            return false;
        }
        
        // Extract control digit (last digit)
        $controlDigit = (int) substr($cui, -1);
        $cuiWithoutControl = substr($cui, 0, -1);
        
        // Calculate expected control digit
        $testKey = '753217532';
        $sum = 0;
        
        // Pad CUI to 9 digits (without control)
        $cuiWithoutControl = str_pad($cuiWithoutControl, 9, '0', STR_PAD_LEFT);
        
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $cuiWithoutControl[$i]) * ((int) $testKey[$i]);
        }
        
        $calculatedControl = ($sum * 10) % 11;
        
        // If result is 10, control digit should be 0
        if ($calculatedControl === 10) {
            $calculatedControl = 0;
        }
        
        return $controlDigit === $calculatedControl;
    }
    
    /**
     * Format CUI (add RO prefix for VAT)
     * 
     * @param string $cui
     * @param bool $addRoPrefix
     * @return string
     */
    public static function format(string $cui, bool $addRoPrefix = false): string
    {
        // Remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        // Remove any non-digit characters
        $cui = preg_replace('/[^0-9]/', '', $cui);
        
        if ($addRoPrefix) {
            return 'RO' . $cui;
        }
        
        return $cui;
    }
    
    /**
     * Extract CUI from various formats
     * 
     * @param string $input
     * @return string|null
     */
    public static function extract(string $input): ?string
    {
        // Try to match RO followed by digits
        if (preg_match('/RO\s*([0-9]{2,10})/i', $input, $matches)) {
            return $matches[1];
        }
        
        // Try to match CUI: or C.U.I. followed by digits
        if (preg_match('/C\.?U\.?I\.?[:\s]+([0-9]{2,10})/i', $input, $matches)) {
            return $matches[1];
        }
        
        // Try to match CIF: followed by digits
        if (preg_match('/C\.?I\.?F\.?[:\s]+([0-9]{2,10})/i', $input, $matches)) {
            return $matches[1];
        }
        
        // Try to match just digits if they look like a CUI
        if (preg_match('/\b([0-9]{2,10})\b/', $input, $matches)) {
            $cui = $matches[1];
            if (self::validate($cui)) {
                return $cui;
            }
        }
        
        return null;
    }
    
    /**
     * Check if string contains a valid CUI
     * 
     * @param string $input
     * @return bool
     */
    public static function contains(string $input): bool
    {
        return self::extract($input) !== null;
    }
}