<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when a network error occurs
 */
class NetworkException extends ContazenException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Network error occurred',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}