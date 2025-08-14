<?php

declare(strict_types=1);

namespace Contazen\Validators;

/**
 * Email validator with additional business rules
 */
class EmailValidator
{
    /**
     * Validate email address
     * 
     * @param string $email
     * @param bool $checkDns Check if domain has MX records
     * @return bool
     */
    public static function validate(string $email, bool $checkDns = false): bool
    {
        // Basic validation using filter_var
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional validation for common issues
        if (!self::validateFormat($email)) {
            return false;
        }
        
        // Check DNS records if requested
        if ($checkDns && !self::validateDns($email)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate email format with additional rules
     * 
     * @param string $email
     * @return bool
     */
    private static function validateFormat(string $email): bool
    {
        // Check for double dots
        if (strpos($email, '..') !== false) {
            return false;
        }
        
        // Split into local and domain parts
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }
        
        [$local, $domain] = $parts;
        
        // Validate local part length (max 64 characters)
        if (strlen($local) > 64) {
            return false;
        }
        
        // Validate domain length (max 255 characters)
        if (strlen($domain) > 255) {
            return false;
        }
        
        // Check if local part starts or ends with a dot
        if ($local[0] === '.' || $local[strlen($local) - 1] === '.') {
            return false;
        }
        
        // Validate domain has at least one dot
        if (strpos($domain, '.') === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate domain has MX records
     * 
     * @param string $email
     * @return bool
     */
    private static function validateDns(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        
        if (empty($domain)) {
            return false;
        }
        
        // Check for MX records
        if (checkdnsrr($domain, 'MX')) {
            return true;
        }
        
        // Fallback to A record check
        return checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }
    
    /**
     * Normalize email address
     * 
     * @param string $email
     * @return string
     */
    public static function normalize(string $email): string
    {
        // Convert to lowercase
        $email = strtolower(trim($email));
        
        // Handle Gmail aliases (remove + and everything after)
        if (self::isGmail($email)) {
            $parts = explode('@', $email);
            if (count($parts) === 2) {
                $local = $parts[0];
                // Remove everything after +
                if (($pos = strpos($local, '+')) !== false) {
                    $local = substr($local, 0, $pos);
                }
                // Remove dots from Gmail addresses
                $local = str_replace('.', '', $local);
                $email = $local . '@' . $parts[1];
            }
        }
        
        return $email;
    }
    
    /**
     * Check if email is from Gmail
     * 
     * @param string $email
     * @return bool
     */
    public static function isGmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, ['gmail.com', 'googlemail.com'], true);
    }
    
    /**
     * Check if email is from a free provider
     * 
     * @param string $email
     * @return bool
     */
    public static function isFreeProvider(string $email): bool
    {
        $freeProviders = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'aol.com', 'mail.com', 'protonmail.com', 'yandex.com',
            'mail.ru', 'gmx.com', 'zoho.com', 'icloud.com',
            'yahoo.ro', 'gmail.ro', 'email.ro'
        ];
        
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, $freeProviders, true);
    }
    
    /**
     * Check if email appears to be disposable/temporary
     * 
     * @param string $email
     * @return bool
     */
    public static function isDisposable(string $email): bool
    {
        $disposableDomains = [
            '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
            'temp-mail.org', 'throwaway.email', 'yopmail.com',
            'tempmail.com', 'trashmail.com', 'fakeinbox.com',
            'sharklasers.com', 'guerrillamailblock.com'
        ];
        
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, $disposableDomains, true);
    }
    
    /**
     * Extract domain from email
     * 
     * @param string $email
     * @return string|null
     */
    public static function getDomain(string $email): ?string
    {
        if (!self::validate($email)) {
            return null;
        }
        
        return substr(strrchr($email, '@'), 1);
    }
    
    /**
     * Extract local part from email
     * 
     * @param string $email
     * @return string|null
     */
    public static function getLocalPart(string $email): ?string
    {
        if (!self::validate($email)) {
            return null;
        }
        
        $parts = explode('@', $email);
        return $parts[0] ?? null;
    }
    
    /**
     * Suggest corrections for common typos
     * 
     * @param string $email
     * @return string|null
     */
    public static function suggestCorrection(string $email): ?string
    {
        $commonTypos = [
            'gmial.com' => 'gmail.com',
            'gmai.com' => 'gmail.com',
            'gmail.co' => 'gmail.com',
            'gmail.con' => 'gmail.com',
            'gmal.com' => 'gmail.com',
            'yahooo.com' => 'yahoo.com',
            'yaho.com' => 'yahoo.com',
            'yahoo.co' => 'yahoo.com',
            'hotmial.com' => 'hotmail.com',
            'hotmal.com' => 'hotmail.com',
            'hotmali.com' => 'hotmail.com',
            'outlok.com' => 'outlook.com',
            'outloook.com' => 'outlook.com'
        ];
        
        $parts = explode('@', strtolower($email));
        if (count($parts) !== 2) {
            return null;
        }
        
        $domain = $parts[1];
        if (isset($commonTypos[$domain])) {
            return $parts[0] . '@' . $commonTypos[$domain];
        }
        
        return null;
    }
}