<?php

namespace App\Examples;

/**
 * File logger implementation
 */
class FileLogger implements LoggerInterface
{
    private array $logs = [];
    private string $logFile;

    public function __construct(string $logFile = '/tmp/app.log')
    {
        $this->logFile = $logFile;
    }

    public function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        $this->logs[] = $logEntry;
        file_put_contents($this->logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
