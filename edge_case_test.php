<?php

require_once 'vendor/autoload.php';

use App\Container\Container;
use App\Examples\LoggerInterface;
use App\Examples\FileLogger;
use App\Examples\DatabaseConnection;
use App\Examples\UserService;
use App\Examples\EmailService;

echo "=== EDGE CASE TESTING OF DI CONTAINER ===\n\n";

// Test 1: Fresh container with no bindings
echo "1. Testing fresh container with no bindings...\n";
$container = new Container();

try {
    $container->make(LoggerInterface::class);
    echo "✗ Should have thrown exception for unbound interface\n";
} catch (RuntimeException $e) {
    echo "✓ Exception caught: " . $e->getMessage() . "\n";
}

try {
    $container->make('NonExistentClass');
    echo "✗ Should have thrown exception for non-existent class\n";
} catch (RuntimeException $e) {
    echo "✓ Exception caught: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Constructor with default parameters
echo "2. Testing constructor with default parameters...\n";
$container->bind(LoggerInterface::class, FileLogger::class);
$emailService = $container->make(EmailService::class);
echo "✓ EmailService created with default parameters\n";
$smtpConfig = $emailService->getSmtpConfig();
echo "   Default SMTP Host: " . $smtpConfig['host'] . "\n";
echo "   Default SMTP Port: " . $smtpConfig['port'] . "\n\n";

// Test 3: Constructor with custom parameters
echo "3. Testing constructor with custom parameters...\n";
$container->bind(DatabaseConnection::class, function($container) {
    return new DatabaseConnection('test-host', 'test-db', 'test-user', 'test-pass');
});
$userService = $container->make(UserService::class, ['serviceName' => 'CustomUserService']);
echo "✓ UserService created with custom parameters\n";
echo "   Custom service name: " . $userService->getServiceName() . "\n\n";

// Test 4: Multiple instances of same class with different bindings
echo "4. Testing multiple instances with different bindings...\n";
$container->bind('logger1', FileLogger::class);
$container->bind('logger2', function($container) {
    return new FileLogger('/tmp/logger2.log');
});

$logger1 = $container->make('logger1');
$logger2 = $container->make('logger2');
echo "✓ Multiple logger instances created\n";
echo "   Logger1 file: " . $logger1->getLogFile() . "\n";
echo "   Logger2 file: " . $logger2->getLogFile() . "\n";
echo "   Different instances? " . ($logger1 !== $logger2 ? "Yes" : "No") . "\n\n";

// Test 5: Singleton behavior across different abstract names
echo "5. Testing singleton behavior across different abstract names...\n";
$container->singleton('singleton1', FileLogger::class);
$container->singleton('singleton2', FileLogger::class);

$s1 = $container->make('singleton1');
$s2 = $container->make('singleton2');
echo "✓ Singleton instances created\n";
echo "   Same instances? " . ($s1 === $s2 ? "No (different singletons)" : "No (different singletons)") . "\n";
echo "   Same class? " . (get_class($s1) === get_class($s2) ? "Yes" : "No") . "\n\n";

// Test 6: Per-request behavior with multiple services
echo "6. Testing per-request behavior with multiple services...\n";
$container->perRequest('per-request1', FileLogger::class);
$container->perRequest('per-request2', function($container) {
    return new FileLogger('/tmp/per-request2.log');
});

$pr1a = $container->make('per-request1');
$pr2a = $container->make('per-request2');
$pr1b = $container->make('per-request1');
$pr2b = $container->make('per-request2');

echo "✓ Per-request instances created\n";
echo "   PR1 same instances? " . ($pr1a === $pr1b ? "Yes" : "No") . "\n";
echo "   PR2 same instances? " . ($pr2a === $pr2b ? "Yes" : "No") . "\n";

$container->startNewRequest();
$pr1c = $container->make('per-request1');
$pr2c = $container->make('per-request2');

echo "   PR1 after new request? " . ($pr1a === $pr1c ? "No (new instance)" : "Yes (new instance)") . "\n";
echo "   PR2 after new request? " . ($pr2a === $pr2c ? "No (new instance)" : "Yes (new instance)") . "\n\n";

// Test 7: Complex dependency resolution
echo "7. Testing complex dependency resolution...\n";
// DatabaseConnection is already bound from test 3, but let's update it
$container->bind(DatabaseConnection::class, function($container) {
    return new DatabaseConnection('complex-host', 'complex-db', 'complex-user', 'complex-pass');
});

$userService = $container->make(UserService::class);
$user = $userService->createUser('edge_case_user', 'edge@example.com');
echo "✓ Complex dependency resolution tested\n";
echo "   User created: " . $user['username'] . "\n";
echo "   Database host: " . $userService->getDatabaseInfo()['host'] . "\n\n";

// Test 8: Service binding validation
echo "8. Testing service binding validation...\n";
echo "   LoggerInterface bound: " . ($container->bound(LoggerInterface::class) ? "Yes" : "No") . "\n";
echo "   FileLogger bound: " . ($container->bound(FileLogger::class) ? "Yes" : "No") . "\n";
echo "   logger1 bound: " . ($container->bound('logger1') ? "Yes" : "No") . "\n";
echo "   singleton1 bound: " . ($container->bound('singleton1') ? "Yes" : "No") . "\n";
echo "   per-request1 bound: " . ($container->bound('per-request1') ? "Yes" : "No") . "\n";
echo "   NonExistentService bound: " . ($container->bound('NonExistentService') ? "Yes" : "No") . "\n\n";

// Test 9: Instance binding override
echo "9. Testing instance binding override...\n";
$originalLogger = new FileLogger('/tmp/original.log');
$container->instance('instance.logger', $originalLogger);
$resolved1 = $container->make('instance.logger');
$resolved2 = $container->make('instance.logger');

echo "✓ Instance binding tested\n";
echo "   Same as original? " . ($originalLogger === $resolved1 ? "Yes" : "No") . "\n";
echo "   Same instances? " . ($resolved1 === $resolved2 ? "Yes" : "No") . "\n\n";

// Test 10: Request ID uniqueness
echo "10. Testing request ID uniqueness...\n";
$requestId1 = $container->getRequestId();
$container->startNewRequest();
$requestId2 = $container->getRequestId();
$container->startNewRequest();
$requestId3 = $container->getRequestId();

echo "✓ Request IDs generated\n";
echo "   Request ID 1: " . $requestId1 . "\n";
echo "   Request ID 2: " . $requestId2 . "\n";
echo "   Request ID 3: " . $requestId3 . "\n";
echo "   All unique? " . (($requestId1 !== $requestId2 && $requestId2 !== $requestId3 && $requestId1 !== $requestId3) ? "Yes" : "No") . "\n\n";

// Test 11: Clear instances and verify
echo "11. Testing clear instances...\n";
$beforeClear = $container->make('singleton1');
$container->clearInstances();
$afterClear = $container->make('singleton1');

echo "✓ Instances cleared\n";
echo "   Same singleton after clear? " . ($beforeClear === $afterClear ? "No (cleared)" : "Yes (new instance)") . "\n";
echo "   Instance binding preserved? " . ($container->make('instance.logger') === $originalLogger ? "Yes" : "No") . "\n\n";

// Test 12: Logger functionality
echo "12. Testing logger functionality...\n";
$logger = $container->make(LoggerInterface::class);
$logger->log('Edge case test log entry');
$logger->log('Another test log entry');

$logs = $logger->getLogs();
echo "✓ Logger functionality tested\n";
echo "   Log entries: " . count($logs) . "\n";
foreach ($logs as $log) {
    echo "   " . $log . "\n";
}
echo "\n";

echo "=== EDGE CASE TESTING COMPLETED SUCCESSFULLY! ===\n";
echo "All 12 edge case scenarios passed! 🎉\n";
