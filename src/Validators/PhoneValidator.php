<?php

declare(strict_types=1);

namespace Contazen\Validators;

/**
 * Phone number validator with focus on Romanian numbers
 */
class PhoneValidator
{
    /**
     * Romanian mobile prefixes
     */
    private const RO_MOBILE_PREFIXES = [
        '0722', '0723', '0724', '0725', '0726', '0727', '0728', '0729', // Orange
        '0730', '0731', '0732', '0733', '0734', '0735', '0736', '0737', '0738', '0739', // Vodafone
        '0740', '0741', '0742', '0743', '0744', '0745', '0746', '0747', '0748', '0749', // Orange
        '0750', '0751', '0752', '0753', '0754', '0755', '0756', '0757', '0758', '0759', // Telekom
        '0760', '0761', '0762', '0763', '0764', '0765', '0766', '0767', '0768', '0769', // Telekom
        '0770', '0771', '0772', '0773', '0774', '0775', '0776', '0777', '0778', '0779', // Digi
        '0780', '0781', '0782', '0783', '0784', '0785', '0786', '0787', '0788', // Telekom
    ];
    
    /**
     * Romanian landline area codes
     */
    private const RO_LANDLINE_PREFIXES = [
        '021', // București
        '0230', '0231', '0232', '0233', '0234', '0235', '0236', '0237', '0238', '0239', // Moldova
        '0240', '0241', '0242', '0243', '0244', '0245', '0246', '0247', '0248', '0249', // Muntenia
        '0250', '0251', '0252', '0253', '0254', '0255', '0256', '0257', '0258', '0259', // Oltenia, Banat
        '0260', '0261', '0262', '0263', '0264', '0265', '0266', '0267', '0268', '0269', // Transilvania
        '0330', '0331', '0332', '0333', '0334', '0335', '0336', '0337', '0338', '0339', // Moldova
        '0340', '0341', '0342', '0343', '0344', '0345', '0346', '0347', '0348', '0349', // Muntenia
        '0350', '0351', '0352', '0353', '0354', '0355', '0356', '0357', '0358', '0359', // Oltenia, Banat
        '0360', '0361', '0362', '0363', '0364', '0365', '0366', '0367', '0368', '0369', // Transilvania
        '0370', '0371', '0372', '0373', '0374', // Special
    ];
    
    /**
     * Validate phone number
     * 
     * @param string $phone
     * @param string $country Country code (RO for Romania)
     * @return bool
     */
    public static function validate(string $phone, string $country = 'RO'): bool
    {
        $phone = self::normalize($phone);
        
        if ($country === 'RO') {
            return self::validateRomanian($phone);
        }
        
        // Generic international validation
        return self::validateInternational($phone);
    }
    
    /**
     * Validate Romanian phone number
     * 
     * @param string $phone
     * @return bool
     */
    public static function validateRomanian(string $phone): bool
    {
        // Remove country code if present
        $phone = preg_replace('/^(\+40|0040)/', '0', $phone);
        
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Romanian numbers should be 10 digits starting with 0
        if (!preg_match('/^0[0-9]{9}$/', $phone)) {
            return false;
        }
        
        // Check if it's a valid mobile number
        $prefix = substr($phone, 0, 4);
        if (in_array($prefix, self::RO_MOBILE_PREFIXES, true)) {
            return true;
        }
        
        // Check landline prefixes
        foreach (self::RO_LANDLINE_PREFIXES as $landlinePrefix) {
            if (strpos($phone, $landlinePrefix) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate international phone number
     * 
     * @param string $phone
     * @return bool
     */
    public static function validateInternational(string $phone): bool
    {
        // Remove all non-digits except + at the beginning
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // International format: + followed by 7-15 digits
        if (preg_match('/^\+[0-9]{7,15}$/', $phone)) {
            return true;
        }
        
        // Allow numbers without + but with country code (10-15 digits)
        if (preg_match('/^[0-9]{10,15}$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Normalize phone number
     * 
     * @param string $phone
     * @return string
     */
    public static function normalize(string $phone): string
    {
        // Remove common formatting characters
        return preg_replace('/[\s\-\(\)\.]/', '', trim($phone));
    }
    
    /**
     * Format Romanian phone number
     * 
     * @param string $phone
     * @param bool $international Use international format
     * @return string|null
     */
    public static function formatRomanian(string $phone, bool $international = false): ?string
    {
        $phone = self::normalize($phone);
        
        // Remove country code if present
        $phone = preg_replace('/^(\+40|0040)/', '0', $phone);
        
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (!self::validateRomanian($phone)) {
            return null;
        }
        
        if ($international) {
            // Format: +40 7xx xxx xxx
            $phone = '+40' . substr($phone, 1);
            return substr($phone, 0, 3) . ' ' . 
                   substr($phone, 3, 3) . ' ' . 
                   substr($phone, 6, 3) . ' ' . 
                   substr($phone, 9, 3);
        }
        
        // Check if mobile
        if (self::isMobile($phone)) {
            // Format: 07xx xxx xxx
            return substr($phone, 0, 4) . ' ' . 
                   substr($phone, 4, 3) . ' ' . 
                   substr($phone, 7, 3);
        }
        
        // Landline - determine format based on prefix length
        if (substr($phone, 0, 3) === '021') {
            // București: 021 xxx xx xx
            return substr($phone, 0, 3) . ' ' . 
                   substr($phone, 3, 3) . ' ' . 
                   substr($phone, 6, 2) . ' ' . 
                   substr($phone, 8, 2);
        }
        
        // Other landlines: 02xx xxx xxx
        return substr($phone, 0, 4) . ' ' . 
               substr($phone, 4, 3) . ' ' . 
               substr($phone, 7, 3);
    }
    
    /**
     * Check if Romanian number is mobile
     * 
     * @param string $phone
     * @return bool
     */
    public static function isMobile(string $phone): bool
    {
        $phone = self::normalize($phone);
        $phone = preg_replace('/^(\+40|0040)/', '0', $phone);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        $prefix = substr($phone, 0, 4);
        return in_array($prefix, self::RO_MOBILE_PREFIXES, true);
    }
    
    /**
     * Get carrier for Romanian mobile number
     * 
     * @param string $phone
     * @return string|null
     */
    public static function getRomanianCarrier(string $phone): ?string
    {
        if (!self::isMobile($phone)) {
            return null;
        }
        
        $phone = self::normalize($phone);
        $phone = preg_replace('/^(\+40|0040)/', '0', $phone);
        $prefix = substr($phone, 0, 4);
        
        // Carrier mapping (simplified, actual portability may differ)
        $carriers = [
            'Orange' => ['0722', '0723', '0724', '0725', '0726', '0727', '0728', '0729',
                        '0740', '0741', '0742', '0743', '0744', '0745', '0746', '0747', '0748', '0749'],
            'Vodafone' => ['0730', '0731', '0732', '0733', '0734', '0735', '0736', '0737', '0738', '0739'],
            'Telekom' => ['0750', '0751', '0752', '0753', '0754', '0755', '0756', '0757', '0758', '0759',
                         '0760', '0761', '0762', '0763', '0764', '0765', '0766', '0767', '0768', '0769',
                         '0780', '0781', '0782', '0783', '0784', '0785', '0786', '0787', '0788'],
            'Digi' => ['0770', '0771', '0772', '0773', '0774', '0775', '0776', '0777', '0778', '0779'],
        ];
        
        foreach ($carriers as $carrier => $prefixes) {
            if (in_array($prefix, $prefixes, true)) {
                return $carrier;
            }
        }
        
        return null;
    }
    
    /**
     * Convert to international format
     * 
     * @param string $phone
     * @param string $defaultCountry
     * @return string|null
     */
    public static function toInternational(string $phone, string $defaultCountry = 'RO'): ?string
    {
        $phone = self::normalize($phone);
        
        // Already international
        if (strpos($phone, '+') === 0) {
            return $phone;
        }
        
        if ($defaultCountry === 'RO' && self::validateRomanian($phone)) {
            // Convert Romanian number to international
            $phone = preg_replace('/^0/', '', $phone);
            return '+40' . $phone;
        }
        
        return null;
    }
}