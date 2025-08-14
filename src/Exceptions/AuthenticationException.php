<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when authentication fails (401 responses)
 */
class AuthenticationException extends ApiException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Authentication failed',
        int $code = 401,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, [], $previous);
    }
}