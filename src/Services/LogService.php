<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * LogService - Simple PSR-3 compatible file logger
 * 
 * This class provides logging functionality that is compliant with the PSR-3 standard.
 * It writes log messages to files with different severity levels.
 */
class LogService implements LoggerInterface
{
    /** @var string Directory where log files are stored */
    private string $logDir;
    
    /** @var string Current date in YYYY-MM-DD format */
    private string $currentDate;
    
    /** @var bool Whether to include backtrace information in log messages */
    private bool $includeBacktrace;
    
    /**
     * Create a new LogService instance
     * 
     * @param string $logDir Directory where log files are stored
     * @param bool $includeBacktrace Whether to include backtrace in log messages
     */
    public function __construct(string $logDir, bool $includeBacktrace = true)
    {
        $this->logDir = rtrim($logDir, '/');
        $this->currentDate = date('Y-m-d');
        $this->includeBacktrace = $includeBacktrace;
        
        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * System is unusable.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * Action must be taken immediately.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * Critical conditions.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * Normal but significant events.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * Interesting events.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * Detailed debug information.
     *
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level Log level
     * @param string $message Log message
     * @param array $context Additional context information
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        // Check if date has changed
        $today = date('Y-m-d');
        if ($today !== $this->currentDate) {
            $this->currentDate = $today;
        }
        
        // Get file path
        $logFile = $this->getLogFile($level);
        
        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Format message
        $formattedMessage = $this->formatMessage($level, $timestamp, $message, $context);
        
        // Write to log file
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
    
    /**
     * Format a log message
     * 
     * @param string $level Log level
     * @param string $timestamp Timestamp
     * @param string $message Log message
     * @param array $context Additional context information
     * @return string Formatted log message
     */
    private function formatMessage(string $level, string $timestamp, string $message, array $context): string
    {
        // Replace placeholders in message
        $message = $this->interpolate($message, $context);
        
        // Get calling file and line
        $backtrace = '';
        if ($this->includeBacktrace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $trace[2] ?? $trace[1] ?? $trace[0] ?? null;
            
            if ($caller) {
                $file = $caller['file'] ?? 'unknown';
                $line = $caller['line'] ?? 'unknown';
                $backtrace = " [{$file}:{$line}]";
            }
        }
        
        // Format the base message
        $formattedMessage = "[$timestamp] [$level]$backtrace: $message";
        
        // Add context data if available (except for 'exception' which is handled differently)
        $contextData = $context;
        unset($contextData['exception']);
        
        if (!empty($contextData)) {
            $formattedMessage .= "\nContext: " . json_encode($contextData, JSON_PRETTY_PRINT);
        }
        
        // Add exception details if available
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $exception = $context['exception'];
            $formattedMessage .= "\nException: " . get_class($exception) .
                " - {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}\n" .
                "Stack trace:\n{$exception->getTraceAsString()}";
        }
        
        return $formattedMessage . "\n";
    }
    
    /**
     * Replace placeholders in the message with context values
     * 
     * @param string $message Log message with placeholders
     * @param array $context Context values to replace placeholders
     * @return string Message with placeholders replaced
     */
    private function interpolate(string $message, array $context): string
    {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // Skip if the value is not a string, number, or stringable object
            if (is_array($val) || is_object($val) && !method_exists($val, '__toString')) {
                continue;
            }
            
            // Format the value as a string
            $replace['{' . $key . '}'] = (string)$val;
        }
        
        // Interpolate replacement values into the message
        return strtr($message, $replace);
    }
    
    /**
     * Get the log file path for a specific level
     * 
     * @param string $level Log level
     * @return string Log file path
     */
    private function getLogFile(string $level): string
    {
        // Group error levels into files
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $fileName = "error-{$this->currentDate}.log";
                break;
                
            case LogLevel::WARNING:
            case LogLevel::NOTICE:
                $fileName = "warning-{$this->currentDate}.log";
                break;
                
            case LogLevel::INFO:
                $fileName = "info-{$this->currentDate}.log";
                break;
                
            case LogLevel::DEBUG:
                $fileName = "debug-{$this->currentDate}.log";
                break;
                
            default:
                $fileName = "app-{$this->currentDate}.log";
        }
        
        return $this->logDir . '/' . $fileName;
    }
}