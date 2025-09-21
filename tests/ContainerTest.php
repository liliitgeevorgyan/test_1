<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Container\Container;
use App\Examples\LoggerInterface;
use App\Examples\FileLogger;
use App\Examples\DatabaseConnection;
use App\Examples\UserService;
use App\Examples\EmailService;

/**
 * Test cases for the DI Container
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCanBindAndResolveSimpleClass()
    {
        $this->container->bind(FileLogger::class);
        
        $instance = $this->container->make(FileLogger::class);
        
        $this->assertInstanceOf(FileLogger::class, $instance);
    }

    public function testCanBindInterfaceToImplementation()
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        
        $instance = $this->container->make(LoggerInterface::class);
        
        $this->assertInstanceOf(FileLogger::class, $instance);
        $this->assertInstanceOf(LoggerInterface::class, $instance);
    }

    public function testSingletonLifecycle()
    {
        $this->container->singleton(FileLogger::class);
        
        $instance1 = $this->container->make(FileLogger::class);
        $instance2 = $this->container->make(FileLogger::class);
        
        $this->assertSame($instance1, $instance2);
    }

    public function testTransientLifecycle()
    {
        $this->container->bind(FileLogger::class, FileLogger::class, 'transient');
        
        $instance1 = $this->container->make(FileLogger::class);
        $instance2 = $this->container->make(FileLogger::class);
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testPerRequestLifecycle()
    {
        $this->container->perRequest(FileLogger::class);
        
        $instance1 = $this->container->make(FileLogger::class);
        $instance2 = $this->container->make(FileLogger::class);
        
        // Should be same within same request
        $this->assertSame($instance1, $instance2);
        
        // Start new request
        $this->container->startNewRequest();
        $instance3 = $this->container->make(FileLogger::class);
        
        // Should be different after new request
        $this->assertNotSame($instance1, $instance3);
    }

    public function testCanBindInstance()
    {
        $logger = new FileLogger('/tmp/test.log');
        $this->container->instance('test.logger', $logger);
        
        $resolved = $this->container->make('test.logger');
        
        $this->assertSame($logger, $resolved);
    }

    public function testCanBindWithClosure()
    {
        $this->container->bind('custom.logger', function($container) {
            return new FileLogger('/tmp/custom.log');
        });
        
        $instance = $this->container->make('custom.logger');
        
        $this->assertInstanceOf(FileLogger::class, $instance);
        $this->assertEquals('/tmp/custom.log', $instance->getLogFile());
    }

    public function testConstructorInjection()
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->bind(DatabaseConnection::class, function($container) {
            return new DatabaseConnection('localhost', 'testdb', 'user', 'pass');
        });
        
        $userService = $this->container->make(UserService::class);
        
        $this->assertInstanceOf(UserService::class, $userService);
        $this->assertInstanceOf(LoggerInterface::class, $userService->getLogger());
    }

    public function testConstructorInjectionWithParameters()
    {
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        $this->container->bind(DatabaseConnection::class, function($container) {
            return new DatabaseConnection('localhost', 'testdb', 'user', 'pass');
        });
        
        $userService = $this->container->make(UserService::class, ['serviceName' => 'CustomUserService']);
        
        $this->assertEquals('CustomUserService', $userService->getServiceName());
    }

    public function testCanCheckIfServiceIsBound()
    {
        $this->assertFalse($this->container->bound('non.existent'));
        
        $this->container->bind('test.service', FileLogger::class);
        $this->assertTrue($this->container->bound('test.service'));
    }

    public function testCanGetBindings()
    {
        $this->container->bind('test.service', FileLogger::class, 'singleton');
        
        $bindings = $this->container->getBindings();
        
        $this->assertArrayHasKey('test.service', $bindings);
        $this->assertEquals(FileLogger::class, $bindings['test.service']['concrete']);
        $this->assertEquals('singleton', $bindings['test.service']['lifecycle']);
    }

    public function testCanClearInstances()
    {
        $this->container->singleton(FileLogger::class);
        
        $instance1 = $this->container->make(FileLogger::class);
        $this->container->clearInstances();
        $instance2 = $this->container->make(FileLogger::class);
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testRequestIdChangesOnNewRequest()
    {
        $requestId1 = $this->container->getRequestId();
        
        $this->container->startNewRequest();
        $requestId2 = $this->container->getRequestId();
        
        $this->assertNotEquals($requestId1, $requestId2);
    }

    public function testComplexDependencyResolution()
    {
        // Set up complex dependency chain
        $this->container->singleton(LoggerInterface::class, FileLogger::class);
        $this->container->bind(DatabaseConnection::class, function($container) {
            return new DatabaseConnection('localhost', 'testdb', 'user', 'pass');
        });
        $this->container->bind(EmailService::class, function($container) {
            return new EmailService(
                $container->make(LoggerInterface::class),
                'smtp.test.com',
                587
            );
        });
        
        $emailService = $this->container->make(EmailService::class);
        
        $this->assertInstanceOf(EmailService::class, $emailService);
        $smtpConfig = $emailService->getSmtpConfig();
        $this->assertEquals('smtp.test.com', $smtpConfig['host']);
        $this->assertEquals(587, $smtpConfig['port']);
    }

    public function testThrowsExceptionForNonExistentClass()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Class [NonExistentClass] not found.');
        
        $this->container->make('NonExistentClass');
    }

    public function testThrowsExceptionForNonInstantiableClass()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Class [App\Examples\LoggerInterface] is not instantiable.');
        
        $this->container->make(LoggerInterface::class);
    }

    public function testThrowsExceptionForUnresolvableDependency()
    {
        // Create a class that requires a non-existent dependency
        $this->container->bind('TestClass', function($container) {
            return new class($container->make('NonExistentDependency')) {
                public function __construct($dependency) {}
            };
        });
        
        $this->expectException(\RuntimeException::class);
        
        $this->container->make('TestClass');
    }

    public function testCanResolveClassWithoutConstructor()
    {
        $instance = $this->container->make(FileLogger::class);
        
        $this->assertInstanceOf(FileLogger::class, $instance);
    }

    public function testCanResolveClassWithDefaultParameters()
    {
        $this->container->bind(EmailService::class, function($container) {
            return new EmailService($container->make(LoggerInterface::class));
        });
        $this->container->bind(LoggerInterface::class, FileLogger::class);
        
        $emailService = $this->container->make(EmailService::class);
        
        $this->assertInstanceOf(EmailService::class, $emailService);
        $smtpConfig = $emailService->getSmtpConfig();
        $this->assertEquals('localhost', $smtpConfig['host']);
        $this->assertEquals(587, $smtpConfig['port']);
    }
}
