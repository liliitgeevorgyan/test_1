# Installation Guide

## Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/milllenanew-droid/test-1.git
   cd test-1
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run tests:**
   ```bash
   composer test
   ```

4. **Run examples:**
   ```bash
   php example_usage.php
   php manual_test.php
   php edge_case_test.php
   ```

## Docker Setup

1. **Build and run with Docker:**
   ```bash
   docker-compose up --build
   ```

2. **Run tests in Docker:**
   ```bash
   docker-compose run test
   ```

## Requirements

- PHP 7.4 or higher
- Composer
- Docker (optional)

## Project Structure

```
├── src/
│   ├── Container/
│   │   └── Container.php          # Main DI Container
│   └── Examples/                  # Example classes
├── tests/
│   └── ContainerTest.php          # Unit tests
├── example_usage.php              # Basic usage example
├── manual_test.php                # Comprehensive manual tests
├── edge_case_test.php             # Edge case tests
├── composer.json                  # Dependencies
├── phpunit.xml                    # Test configuration
├── Dockerfile                     # Docker configuration
├── docker-compose.yml             # Docker services
└── README.md                      # Documentation
```

## Features

- ✅ Service registration by name, interface, or implementation
- ✅ Constructor injection with type-hints
- ✅ Lifecycle management (singleton, per-request, transient)
- ✅ Interface binding to implementations
- ✅ Factory functions and instance binding
- ✅ Comprehensive test coverage
- ✅ Docker support
