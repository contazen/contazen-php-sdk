<?php

declare(strict_types=1);

namespace Contazen\Validators;

/**
 * Romanian CNP (Cod Numeric Personal) validator
 */
class CnpValidator
{
    private const CONTROL_KEY = '279146358279';
    
    /**
     * Validate Romanian CNP
     * 
     * @param string $cnp
     * @return bool
     */
    public static function validate(string $cnp): bool
    {
        // Remove any non-digit characters
        $cnp = preg_replace('/[^0-9]/', '', $cnp);
        
        // CNP must be exactly 13 digits
        if (!preg_match('/^[0-9]{13}$/', $cnp)) {
            return false;
        }
        
        // Validate first digit (sex and century)
        $firstDigit = (int) $cnp[0];
        if ($firstDigit < 1 || $firstDigit > 9) {
            return false;
        }
        
        // Validate birth date
        if (!self::validateBirthDate($cnp)) {
            return false;
        }
        
        // Validate county code (digits 7-8)
        $countyCode = (int) substr($cnp, 7, 2);
        if ($countyCode < 1 || $countyCode > 52) {
            return false;
        }
        
        // Validate control digit
        return self::validateControlDigit($cnp);
    }
    
    /**
     * Validate birth date from CNP
     * 
     * @param string $cnp
     * @return bool
     */
    private static function validateBirthDate(string $cnp): bool
    {
        $year = (int) substr($cnp, 1, 2);
        $month = (int) substr($cnp, 3, 2);
        $day = (int) substr($cnp, 5, 2);
        
        // Validate month
        if ($month < 1 || $month > 12) {
            return false;
        }
        
        // Get full year based on first digit
        $firstDigit = (int) $cnp[0];
        switch ($firstDigit) {
            case 1:
            case 2:
                $fullYear = 1900 + $year;
                break;
            case 3:
            case 4:
                $fullYear = 1800 + $year;
                break;
            case 5:
            case 6:
                $fullYear = 2000 + $year;
                break;
            case 7:
            case 8:
            case 9:
                // Foreign residents, use 1900 or 2000 based on year
                $fullYear = ($year >= 50) ? 1900 + $year : 2000 + $year;
                break;
            default:
                return false;
        }
        
        // Validate day for the given month and year
        return checkdate($month, $day, $fullYear);
    }
    
    /**
     * Validate control digit
     * 
     * @param string $cnp
     * @return bool
     */
    private static function validateControlDigit(string $cnp): bool
    {
        $sum = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $cnp[$i]) * ((int) self::CONTROL_KEY[$i]);
        }
        
        $remainder = $sum % 11;
        $expectedControl = ($remainder === 10) ? 1 : $remainder;
        $actualControl = (int) $cnp[12];
        
        return $expectedControl === $actualControl;
    }
    
    /**
     * Extract birth date from CNP
     * 
     * @param string $cnp
     * @return \DateTime|null
     */
    public static function getBirthDate(string $cnp): ?\DateTime
    {
        if (!self::validate($cnp)) {
            return null;
        }
        
        $year = (int) substr($cnp, 1, 2);
        $month = (int) substr($cnp, 3, 2);
        $day = (int) substr($cnp, 5, 2);
        
        $firstDigit = (int) $cnp[0];
        switch ($firstDigit) {
            case 1:
            case 2:
                $fullYear = 1900 + $year;
                break;
            case 3:
            case 4:
                $fullYear = 1800 + $year;
                break;
            case 5:
            case 6:
                $fullYear = 2000 + $year;
                break;
            case 7:
            case 8:
            case 9:
                $fullYear = ($year >= 50) ? 1900 + $year : 2000 + $year;
                break;
            default:
                return null;
        }
        
        try {
            return new \DateTime(sprintf('%04d-%02d-%02d', $fullYear, $month, $day));
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get gender from CNP
     * 
     * @param string $cnp
     * @return string|null 'M' for male, 'F' for female
     */
    public static function getGender(string $cnp): ?string
    {
        if (!self::validate($cnp)) {
            return null;
        }
        
        $firstDigit = (int) $cnp[0];
        
        // Odd numbers are male, even numbers are female
        if (in_array($firstDigit, [1, 3, 5, 7], true)) {
            return 'M';
        }
        
        if (in_array($firstDigit, [2, 4, 6, 8], true)) {
            return 'F';
        }
        
        // 9 is used for foreign residents, gender cannot be determined
        return null;
    }
    
    /**
     * Get county code from CNP
     * 
     * @param string $cnp
     * @return string|null
     */
    public static function getCountyCode(string $cnp): ?string
    {
        if (!self::validate($cnp)) {
            return null;
        }
        
        return substr($cnp, 7, 2);
    }
    
    /**
     * Get age from CNP
     * 
     * @param string $cnp
     * @param \DateTime|null $referenceDate
     * @return int|null
     */
    public static function getAge(string $cnp, ?\DateTime $referenceDate = null): ?int
    {
        $birthDate = self::getBirthDate($cnp);
        
        if (!$birthDate) {
            return null;
        }
        
        $referenceDate = $referenceDate ?? new \DateTime();
        $interval = $birthDate->diff($referenceDate);
        
        return $interval->y;
    }
    
    /**
     * Check if person is adult (18+)
     * 
     * @param string $cnp
     * @return bool
     */
    public static function isAdult(string $cnp): bool
    {
        $age = self::getAge($cnp);
        return $age !== null && $age >= 18;
    }
    
    /**
     * Format CNP with separators for readability
     * 
     * @param string $cnp
     * @param string $separator
     * @return string
     */
    public static function format(string $cnp, string $separator = ' '): string
    {
        $cnp = preg_replace('/[^0-9]/', '', $cnp);
        
        if (strlen($cnp) !== 13) {
            return $cnp;
        }
        
        // Format: S YY MM DD JJ NNN C
        return substr($cnp, 0, 1) . $separator .
               substr($cnp, 1, 2) . $separator .
               substr($cnp, 3, 2) . $separator .
               substr($cnp, 5, 2) . $separator .
               substr($cnp, 7, 2) . $separator .
               substr($cnp, 9, 3) . $separator .
               substr($cnp, 12, 1);
    }
}