<?php

namespace App\Examples;

/**
 * Email service for demonstration
 */
class EmailService
{
    private LoggerInterface $logger;
    private string $smtpHost;
    private int $smtpPort;

    public function __construct(LoggerInterface $logger, string $smtpHost = 'localhost', int $smtpPort = 587)
    {
        $this->logger = $logger;
        $this->smtpHost = $smtpHost;
        $this->smtpPort = $smtpPort;
    }

    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $this->logger->log("Sending email to: {$to} with subject: {$subject}");
        
        // Simulate email sending
        $this->logger->log("Email sent successfully to: {$to}");
        
        return true;
    }

    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->smtpHost,
            'port' => $this->smtpPort
        ];
    }
}
