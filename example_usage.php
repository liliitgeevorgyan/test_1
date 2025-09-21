<?php

require_once 'vendor/autoload.php';

use App\Container\Container;
use App\Examples\LoggerInterface;
use App\Examples\FileLogger;
use App\Examples\DatabaseConnection;
use App\Examples\UserService;
use App\Examples\EmailService;

echo "=== Dependency Injection Container Demo ===\n\n";

// Create container instance
$container = new Container();

echo "1. Binding services with different lifecycles:\n";
echo "---------------------------------------------\n";

// Bind interface to implementation (singleton)
$container->singleton(LoggerInterface::class, FileLogger::class);
echo "✓ Bound LoggerInterface to FileLogger as singleton\n";

// Bind with custom parameters
$container->bind(DatabaseConnection::class, function($container) {
    return new DatabaseConnection('localhost', 'myapp', 'user', 'password');
}, 'singleton');
echo "✓ Bound DatabaseConnection with custom parameters as singleton\n";

// Bind per-request service
$container->perRequest(UserService::class);
echo "✓ Bound UserService as per-request\n";

// Bind transient service
$container->bind(EmailService::class, function($container) {
    return new EmailService(
        $container->make(LoggerInterface::class),
        'smtp.example.com',
        587
    );
}, 'transient');
echo "✓ Bound EmailService as transient with custom factory\n\n";

echo "2. Resolving services and demonstrating lifecycle:\n";
echo "--------------------------------------------------\n";

// Resolve services
$logger1 = $container->make(LoggerInterface::class);
$logger2 = $container->make(LoggerInterface::class);
echo "Logger instances are " . ($logger1 === $logger2 ? "SAME (singleton)" : "DIFFERENT") . "\n";

$db1 = $container->make(DatabaseConnection::class);
$db2 = $container->make(DatabaseConnection::class);
echo "Database instances are " . ($db1 === $db2 ? "SAME (singleton)" : "DIFFERENT") . "\n";

$userService1 = $container->make(UserService::class);
$userService2 = $container->make(UserService::class);
echo "UserService instances are " . ($userService1 === $userService2 ? "SAME (per-request)" : "DIFFERENT") . "\n";

$emailService1 = $container->make(EmailService::class);
$emailService2 = $container->make(EmailService::class);
echo "EmailService instances are " . ($emailService1 === $emailService2 ? "SAME" : "DIFFERENT (transient)") . "\n\n";

echo "3. Testing per-request lifecycle:\n";
echo "--------------------------------\n";
echo "Current request ID: " . $container->getRequestId() . "\n";

// Start new request
$container->startNewRequest();
echo "New request ID: " . $container->getRequestId() . "\n";

$userService3 = $container->make(UserService::class);
echo "UserService instances after new request are " . ($userService1 === $userService3 ? "SAME" : "DIFFERENT (new per-request instance)") . "\n\n";

echo "4. Using resolved services:\n";
echo "---------------------------\n";

// Use the user service
$user = $userService1->createUser('john_doe', 'john@example.com');
echo "Created user: " . json_encode($user, JSON_PRETTY_PRINT) . "\n";

// Use the email service
$emailService = $container->make(EmailService::class);
$emailSent = $emailService->sendEmail('john@example.com', 'Welcome!', 'Welcome to our application!');
echo "Email sent: " . ($emailSent ? "Yes" : "No") . "\n\n";

echo "5. Checking service information:\n";
echo "--------------------------------\n";
echo "UserService name: " . $userService1->getServiceName() . "\n";
echo "Database info: " . json_encode($userService1->getDatabaseInfo(), JSON_PRETTY_PRINT) . "\n";
echo "SMTP config: " . json_encode($emailService->getSmtpConfig(), JSON_PRETTY_PRINT) . "\n\n";

echo "6. Logger output:\n";
echo "-----------------\n";
$logs = $logger1->getLogs();
foreach ($logs as $log) {
    echo $log . "\n";
}

echo "\n7. Testing service binding checks:\n";
echo "----------------------------------\n";
echo "LoggerInterface bound: " . ($container->bound(LoggerInterface::class) ? "Yes" : "No") . "\n";
echo "NonExistentService bound: " . ($container->bound('NonExistentService') ? "Yes" : "No") . "\n";

echo "\n8. Binding an instance directly:\n";
echo "--------------------------------\n";
$customLogger = new FileLogger('/tmp/custom.log');
$container->instance('custom.logger', $customLogger);
$resolvedCustomLogger = $container->make('custom.logger');
echo "Custom logger instances are " . ($customLogger === $resolvedCustomLogger ? "SAME" : "DIFFERENT") . "\n";

echo "\n=== Demo completed successfully! ===\n";
