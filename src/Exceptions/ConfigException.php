<?php

namespace App\Exceptions;

/**
 * ConfigException - Exception for configuration-related errors
 * 
 * This exception is thrown when there are issues with the configuration system,
 * such as missing environment variables, unauthorized access attempts,
 * or initialization failures.
 */
class ConfigException extends \Exception
{
    /**
     * Create a new ConfigException instance
     *
     * @param string $message Exception message
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}