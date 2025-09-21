# Lightweight Dependency Injection Container

A lightweight, feature-rich Dependency Injection (DI) container for PHP 7.4+ that provides automatic dependency resolution, lifecycle management, and constructor injection capabilities.

## Features

- **Service Registration**: Bind services by name, interface, or implementation
- **Constructor Injection**: Automatic dependency resolution using type-hints
- **Lifecycle Management**: Support for singleton, per-request, and transient lifecycles
- **Interface Binding**: Bind interfaces to their implementations
- **Factory Functions**: Use closures for complex service creation
- **Instance Binding**: Bind pre-created instances
- **Request Scoping**: Per-request lifecycle management

## Installation

### Using Composer

```bash
composer install
```

### Using Docker

```bash
docker-compose up --build
```

## Quick Start

```php
<?php

use App\Container\Container;
use App\Examples\LoggerInterface;
use App\Examples\FileLogger;

// Create container
$container = new Container();

// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);

// Resolve service
$logger = $container->make(LoggerInterface::class);
```

## Usage Examples

### Basic Service Binding

```php
// Bind a class to itself
$container->bind(FileLogger::class);

// Bind interface to implementation
$container->bind(LoggerInterface::class, FileLogger::class);

// Bind with custom factory
$container->bind('custom.service', function($container) {
    return new CustomService($container->make(LoggerInterface::class));
});
```

### Lifecycle Management

```php
// Singleton - same instance every time
$container->singleton(LoggerInterface::class, FileLogger::class);

// Per-request - same instance within a request
$container->perRequest(UserService::class);

// Transient - new instance every time (default)
$container->bind(EmailService::class, EmailService::class, 'transient');
```

### Constructor Injection

```php
class UserService
{
    public function __construct(
        LoggerInterface $logger,
        DatabaseConnection $db,
        string $serviceName = 'UserService'
    ) {
        // Dependencies are automatically injected
    }
}

// Container automatically resolves dependencies
$userService = $container->make(UserService::class);
```

### Binding Instances

```php
$logger = new FileLogger('/tmp/app.log');
$container->instance('app.logger', $logger);

$resolvedLogger = $container->make('app.logger');
// $resolvedLogger === $logger
```

### Request Lifecycle

```php
// Start new request (clears per-request instances)
$container->startNewRequest();

// Get current request ID
$requestId = $container->getRequestId();
```

## API Reference

### Container Methods

#### `bind(string $abstract, mixed $concrete = null, string $lifecycle = 'transient')`
Bind a service to the container.

- `$abstract`: The abstract identifier (interface, class name, or alias)
- `$concrete`: The concrete implementation (class name, closure, or instance)
- `$lifecycle`: The lifecycle type ('singleton', 'per-request', 'transient')

#### `singleton(string $abstract, mixed $concrete = null)`
Bind a singleton service (same instance every time).

#### `perRequest(string $abstract, mixed $concrete = null)`
Bind a per-request service (same instance within a request).

#### `instance(string $abstract, mixed $instance)`
Bind a pre-created instance.

#### `make(string $abstract, array $parameters = [])`
Resolve a service from the container.

#### `bound(string $abstract): bool`
Check if a service is bound.

#### `getBindings(): array`
Get all service bindings.

#### `clearInstances(): void`
Clear all cached instances (useful for testing).

#### `startNewRequest(): void`
Start a new request (clears per-request instances).

## Lifecycle Types

### Singleton
- **Behavior**: Same instance returned every time
- **Use Case**: Expensive resources, configuration objects
- **Example**: Database connections, loggers

### Per-Request
- **Behavior**: Same instance within a request, new instance for new requests
- **Use Case**: Request-scoped services, user sessions
- **Example**: User services, request handlers

### Transient
- **Behavior**: New instance created every time
- **Use Case**: Stateless services, data transfer objects
- **Example**: Email services, validators

## Testing

Run the test suite:

```bash
# Using Composer
composer test

# Using Docker
docker-compose run test

# With coverage
composer test-coverage
```

## Example Application

See `example_usage.php` for a comprehensive demonstration of all container features.

```bash
php example_usage.php
```

## Docker Support

The project includes Docker configuration for easy development and testing:

```bash
# Build and run
docker-compose up --build

# Run tests
docker-compose run test

# Access MySQL
docker-compose exec mysql mysql -u user -p testdb
```

## Requirements

- PHP 7.4 or higher
- Composer (for dependency management)
- Docker (optional, for containerized development)

## Architecture

The DI container uses PHP's Reflection API to analyze class constructors and automatically resolve dependencies. It maintains separate storage for different lifecycle types and provides a clean, intuitive API for service registration and resolution.

### Key Components

1. **Container**: Main service container class
2. **Service Binding**: Registration of services with different lifecycles
3. **Dependency Resolution**: Automatic constructor injection
4. **Lifecycle Management**: Instance caching and cleanup
5. **Reflection**: Type-hint analysis and parameter resolution

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

MIT License - see LICENSE file for details.
# test_1
