<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when API returns an error response
 */
class ApiException extends ContazenException
{
    private array $responseBody;
    
    /**
     * @param string $message
     * @param int $code HTTP status code
     * @param array|null $responseBody Full response body from API
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseBody = $responseBody ?? [];
    }
    
    /**
     * Get the full response body from the API
     * 
     * @return array
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
    
    /**
     * Get the error type if available
     * 
     * @return string|null
     */
    public function getErrorType(): ?string
    {
        return $this->responseBody['type'] ?? null;
    }
    
    /**
     * Get the error code if available
     * 
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->responseBody['code'] ?? null;
    }
}