<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when validation fails (422 responses)
 */
class ValidationException extends ApiException
{
    private array $errors;
    
    /**
     * @param string $message
     * @param int $code
     * @param array $errors Validation errors from API
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Validation failed',
        int $code = 422,
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, ['errors' => $errors], $previous);
        $this->errors = $errors;
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if a specific field has errors
     * 
     * @param string $field
     * @return bool
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}