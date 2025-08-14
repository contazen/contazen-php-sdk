<?php

declare(strict_types=1);

namespace Contazen\Exceptions;

/**
 * Base exception for all Contazen SDK exceptions
 */
class ContazenException extends \Exception
{
    /**
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}