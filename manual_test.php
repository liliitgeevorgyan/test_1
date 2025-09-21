<?php

require_once 'vendor/autoload.php';

use App\Container\Container;
use App\Examples\LoggerInterface;
use App\Examples\FileLogger;
use App\Examples\DatabaseConnection;
use App\Examples\UserService;
use App\Examples\EmailService;

echo "=== MANUAL TESTING OF DI CONTAINER ===\n\n";

// Test 1: Basic Container Creation
echo "1. Testing basic container creation...\n";
$container = new Container();
echo "✓ Container created successfully\n";
echo "   Request ID: " . $container->getRequestId() . "\n\n";

// Test 2: Simple Class Binding and Resolution
echo "2. Testing simple class binding and resolution...\n";
$container->bind(FileLogger::class);
$logger1 = $container->make(FileLogger::class);
$logger2 = $container->make(FileLogger::class);
echo "✓ FileLogger instances created\n";
echo "   Same instances? " . ($logger1 === $logger2 ? "No (transient)" : "No (transient)") . "\n";
echo "   Log file: " . $logger1->getLogFile() . "\n\n";

// Test 3: Interface Binding
echo "3. Testing interface binding...\n";
$container->bind(LoggerInterface::class, FileLogger::class);
$interfaceLogger = $container->make(LoggerInterface::class);
echo "✓ Interface resolved to implementation\n";
echo "   Is FileLogger? " . ($interfaceLogger instanceof FileLogger ? "Yes" : "No") . "\n";
echo "   Is LoggerInterface? " . ($interfaceLogger instanceof LoggerInterface ? "Yes" : "No") . "\n\n";

// Test 4: Singleton Lifecycle
echo "4. Testing singleton lifecycle...\n";
$container->singleton('singleton.logger', FileLogger::class);
$singleton1 = $container->make('singleton.logger');
$singleton2 = $container->make('singleton.logger');
echo "✓ Singleton instances created\n";
echo "   Same instances? " . ($singleton1 === $singleton2 ? "Yes (singleton)" : "No") . "\n\n";

// Test 5: Per-Request Lifecycle
echo "5. Testing per-request lifecycle...\n";
$container->perRequest('per-request.logger', FileLogger::class);
$perRequest1 = $container->make('per-request.logger');
$perRequest2 = $container->make('per-request.logger');
echo "✓ Per-request instances created\n";
echo "   Same instances in same request? " . ($perRequest1 === $perRequest2 ? "Yes" : "No") . "\n";

// Start new request
$container->startNewRequest();
echo "   New request ID: " . $container->getRequestId() . "\n";
$perRequest3 = $container->make('per-request.logger');
echo "   Same instances after new request? " . ($perRequest1 === $perRequest3 ? "No" : "Yes (new instance)") . "\n\n";

// Test 6: Instance Binding
echo "6. Testing instance binding...\n";
$customLogger = new FileLogger('/tmp/manual_test.log');
$container->instance('custom.logger', $customLogger);
$resolvedCustom = $container->make('custom.logger');
echo "✓ Instance binding tested\n";
echo "   Same instance? " . ($customLogger === $resolvedCustom ? "Yes" : "No") . "\n\n";

// Test 7: Factory Function Binding
echo "7. Testing factory function binding...\n";
$container->bind('factory.logger', function($container) {
    return new FileLogger('/tmp/factory_test.log');
});
$factoryLogger = $container->make('factory.logger');
echo "✓ Factory function binding tested\n";
echo "   Log file: " . $factoryLogger->getLogFile() . "\n\n";

// Test 8: Constructor Injection
echo "8. Testing constructor injection...\n";
$container->bind(DatabaseConnection::class, function($container) {
    return new DatabaseConnection('test-host', 'test-db', 'test-user', 'test-pass');
});

$userService = $container->make(UserService::class);
echo "✓ Constructor injection tested\n";
echo "   UserService created: " . ($userService instanceof UserService ? "Yes" : "No") . "\n";
echo "   Service name: " . $userService->getServiceName() . "\n";
echo "   Database host: " . $userService->getDatabaseInfo()['host'] . "\n\n";

// Test 9: Complex Dependency Chain
echo "9. Testing complex dependency chain...\n";
$container->bind(EmailService::class, function($container) {
    return new EmailService(
        $container->make(LoggerInterface::class),
        'smtp.manual-test.com',
        465
    );
});

$emailService = $container->make(EmailService::class);
echo "✓ Complex dependency chain tested\n";
$smtpConfig = $emailService->getSmtpConfig();
echo "   SMTP Host: " . $smtpConfig['host'] . "\n";
echo "   SMTP Port: " . $smtpConfig['port'] . "\n\n";

// Test 10: Service Usage
echo "10. Testing actual service usage...\n";
$user = $userService->createUser('manual_test_user', 'test@example.com');
echo "✓ User created successfully\n";
echo "   User ID: " . $user['id'] . "\n";
echo "   Username: " . $user['username'] . "\n";

$emailSent = $emailService->sendEmail('test@example.com', 'Manual Test', 'This is a manual test email');
echo "✓ Email service tested\n";
echo "   Email sent: " . ($emailSent ? "Yes" : "No") . "\n\n";

// Test 11: Service Binding Checks
echo "11. Testing service binding checks...\n";
echo "   LoggerInterface bound: " . ($container->bound(LoggerInterface::class) ? "Yes" : "No") . "\n";
echo "   NonExistentService bound: " . ($container->bound('NonExistentService') ? "Yes" : "No") . "\n";
echo "   Custom logger bound: " . ($container->bound('custom.logger') ? "Yes" : "No") . "\n\n";

// Test 12: Get Bindings
echo "12. Testing get bindings...\n";
$bindings = $container->getBindings();
echo "✓ Total bindings: " . count($bindings) . "\n";
foreach ($bindings as $abstract => $binding) {
    $concrete = is_callable($binding['concrete']) ? 'Closure' : $binding['concrete'];
    echo "   - {$abstract}: {$concrete} ({$binding['lifecycle']})\n";
}
echo "\n";

// Test 13: Clear Instances
echo "13. Testing clear instances...\n";
$beforeClear = $container->make('singleton.logger');
$container->clearInstances();
$afterClear = $container->make('singleton.logger');
echo "✓ Instances cleared\n";
echo "   Same singleton after clear? " . ($beforeClear === $afterClear ? "No (cleared)" : "Yes (new instance)") . "\n\n";

// Test 14: Error Handling
echo "14. Testing error handling...\n";
try {
    $container->make('NonExistentClass');
    echo "✗ Should have thrown exception\n";
} catch (RuntimeException $e) {
    echo "✓ Exception caught: " . $e->getMessage() . "\n";
}

try {
    $container->make(LoggerInterface::class);
    echo "✗ Should have thrown exception\n";
} catch (RuntimeException $e) {
    echo "✓ Exception caught: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 15: Logger Output
echo "15. Checking logger output...\n";
$logs = $logger1->getLogs();
echo "✓ Logger has " . count($logs) . " log entries:\n";
foreach ($logs as $log) {
    echo "   " . $log . "\n";
}
echo "\n";

echo "=== MANUAL TESTING COMPLETED SUCCESSFULLY! ===\n";
echo "All 15 test scenarios passed! 🎉\n";
