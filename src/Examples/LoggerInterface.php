<?php

namespace App\Examples;

/**
 * Logger interface for demonstration
 */
interface LoggerInterface
{
    public function log(string $message): void;
    public function getLogs(): array;
}
