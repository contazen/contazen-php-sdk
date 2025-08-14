<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Exception thrown when a resource is not found (404 responses)
 */
class NotFoundException extends ApiException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Resource not found',
        int $code = 404,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, [], $previous);
    }
}