<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when rate limit is exceeded (429 responses)
 */
class RateLimitException extends ApiException
{
    private array $rateLimitInfo;
    
    /**
     * @param string $message
     * @param int $code
     * @param array $rateLimitInfo Rate limit information from headers
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        array $rateLimitInfo = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, [], $previous);
        $this->rateLimitInfo = $rateLimitInfo;
    }
    
    /**
     * Get rate limit information
     * 
     * @return array{limit: int, remaining: int, reset: int}
     */
    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }
    
    /**
     * Get the limit
     * 
     * @return int
     */
    public function getLimit(): int
    {
        return $this->rateLimitInfo['limit'] ?? 0;
    }
    
    /**
     * Get remaining requests
     * 
     * @return int
     */
    public function getRemaining(): int
    {
        return $this->rateLimitInfo['remaining'] ?? 0;
    }
    
    /**
     * Get reset timestamp
     * 
     * @return int
     */
    public function getResetTimestamp(): int
    {
        return $this->rateLimitInfo['reset'] ?? 0;
    }
    
    /**
     * Get seconds until reset
     * 
     * @return int
     */
    public function getSecondsUntilReset(): int
    {
        $reset = $this->getResetTimestamp();
        if ($reset === 0) {
            return 0;
        }
        
        return max(0, $reset - time());
    }
}