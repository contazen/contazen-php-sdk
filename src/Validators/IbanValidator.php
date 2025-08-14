<?php

declare(strict_types=1);

namespace Contazen\Validators;

/**
 * IBAN (International Bank Account Number) validator
 */
class IbanValidator
{
    /**
     * IBAN lengths by country code
     */
    private const IBAN_LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28,
        'BA' => 20, 'BE' => 16, 'BG' => 22, 'BH' => 22, 'BR' => 29,
        'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24,
        'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29,
        'ES' => 24, 'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22,
        'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27, 'GT' => 28,
        'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IS' => 26,
        'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28,
        'LC' => 32, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21,
        'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19, 'MR' => 27,
        'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15, 'PK' => 24,
        'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24,
        'RS' => 22, 'SA' => 24, 'SE' => 24, 'SI' => 19, 'SK' => 24,
        'SM' => 27, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22,
        'VG' => 24, 'XK' => 20
    ];
    
    /**
     * Validate IBAN
     * 
     * @param string $iban
     * @return bool
     */
    public static function validate(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(preg_replace('/\s/', '', $iban));
        
        // Check basic format (2 letters + 2 digits + alphanumeric)
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }
        
        // Extract country code
        $countryCode = substr($iban, 0, 2);
        
        // Check if country code is known and validate length
        if (isset(self::IBAN_LENGTHS[$countryCode])) {
            if (strlen($iban) !== self::IBAN_LENGTHS[$countryCode]) {
                return false;
            }
        } else {
            // Unknown country code, apply generic length check
            if (strlen($iban) < 15 || strlen($iban) > 34) {
                return false;
            }
        }
        
        // Validate checksum using mod-97 algorithm
        return self::validateChecksum($iban);
    }
    
    /**
     * Validate IBAN checksum using mod-97 algorithm
     * 
     * @param string $iban
     * @return bool
     */
    private static function validateChecksum(string $iban): bool
    {
        // Move first 4 characters to the end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        
        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }
        
        // Calculate mod 97 using bcmod for large numbers
        if (function_exists('bcmod')) {
            return bcmod($numeric, '97') === '1';
        }
        
        // Fallback for systems without bcmath
        return self::mod97($numeric) === 1;
    }
    
    /**
     * Calculate mod 97 for large numbers without bcmath
     * 
     * @param string $number
     * @return int
     */
    private static function mod97(string $number): int
    {
        $checksum = 0;
        
        for ($i = 0; $i < strlen($number); $i++) {
            $checksum = ($checksum * 10 + (int) $number[$i]) % 97;
        }
        
        return $checksum;
    }
    
    /**
     * Format IBAN with spaces
     * 
     * @param string $iban
     * @param int $groupSize
     * @return string
     */
    public static function format(string $iban, int $groupSize = 4): string
    {
        // Remove existing spaces and convert to uppercase
        $iban = strtoupper(preg_replace('/\s/', '', $iban));
        
        // Add spaces every $groupSize characters
        return trim(chunk_split($iban, $groupSize, ' '));
    }
    
    /**
     * Get country code from IBAN
     * 
     * @param string $iban
     * @return string|null
     */
    public static function getCountryCode(string $iban): ?string
    {
        $iban = preg_replace('/\s/', '', $iban);
        
        if (strlen($iban) < 2) {
            return null;
        }
        
        return strtoupper(substr($iban, 0, 2));
    }
    
    /**
     * Get bank code from IBAN (if applicable for country)
     * 
     * @param string $iban
     * @return string|null
     */
    public static function getBankCode(string $iban): ?string
    {
        $iban = strtoupper(preg_replace('/\s/', '', $iban));
        $countryCode = self::getCountryCode($iban);
        
        if (!$countryCode) {
            return null;
        }
        
        // Bank code positions vary by country
        switch ($countryCode) {
            case 'RO': // Romania: positions 4-7 (4 characters)
                return substr($iban, 4, 4);
            case 'DE': // Germany: positions 4-11 (8 characters)
                return substr($iban, 4, 8);
            case 'FR': // France: positions 4-8 (5 characters)
                return substr($iban, 4, 5);
            case 'GB': // UK: positions 4-7 (4 characters)
                return substr($iban, 4, 4);
            case 'IT': // Italy: positions 5-9 (5 characters)
                return substr($iban, 5, 5);
            case 'ES': // Spain: positions 4-7 (4 characters)
                return substr($iban, 4, 4);
            default:
                return null;
        }
    }
    
    /**
     * Validate Romanian IBAN specifically
     * 
     * @param string $iban
     * @return bool
     */
    public static function validateRomanian(string $iban): bool
    {
        if (!self::validate($iban)) {
            return false;
        }
        
        $countryCode = self::getCountryCode($iban);
        return $countryCode === 'RO';
    }
    
    /**
     * Generate check digits for IBAN
     * 
     * @param string $countryCode
     * @param string $accountIdentifier
     * @return string
     */
    public static function generateCheckDigits(string $countryCode, string $accountIdentifier): string
    {
        $countryCode = strtoupper($countryCode);
        $accountIdentifier = strtoupper($accountIdentifier);
        
        // Temporarily use '00' as check digits
        $tempIban = $countryCode . '00' . $accountIdentifier;
        
        // Rearrange: move first 4 characters to end
        $rearranged = substr($tempIban, 4) . substr($tempIban, 0, 4);
        
        // Convert to numeric
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }
        
        // Calculate check digits
        if (function_exists('bcmod')) {
            $checkDigits = 98 - (int) bcmod($numeric, '97');
        } else {
            $checkDigits = 98 - self::mod97($numeric);
        }
        
        return str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT);
    }
}