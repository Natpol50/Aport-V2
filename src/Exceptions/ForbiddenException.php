<?php

namespace App\Exceptions;

/**
 * ForbiddenException - Exception for 403 Forbidden errors
 * 
 * This exception is thrown when a user tries to access a resource
 * they don't have permission to access.
 */
class ForbiddenException extends \Exception
{
    /**
     * Create a new ForbiddenException instance
     *
     * @param string $message Exception message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message = "Access denied", int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}