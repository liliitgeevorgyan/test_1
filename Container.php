<?php

namespace App\Container;

use ReflectionClass;
use ReflectionParameter;
use InvalidArgumentException;
use RuntimeException;

/**
 * Lightweight Dependency Injection Container
 * 
 * Features:
 * - Service registration by name, interface, or implementation
 * - Constructor injection with type-hints
 * - Lifecycle management (singleton, per-request, transient)
 * - Automatic dependency resolution
 */
class Container
{
    /**
     * Service bindings
     * @var array
     */
    private array $bindings = [];

    /**
     * Singleton instances
     * @var array
     */
    private array $instances = [];

    /**
     * Per-request instances
     * @var array
     */
    private array $requestInstances = [];

    /**
     * Request ID for per-request lifecycle
     * @var string
     */
    private string $requestId;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requestId = uniqid('request_', true);
    }

    /**
     * Bind a service to the container
     * 
     * @param string $abstract The abstract identifier (interface, class name, or alias)
     * @param mixed $concrete The concrete implementation (class name, closure, or instance)
     * @param string $lifecycle The lifecycle type (singleton, per-request, transient)
     * @return void
     */
    public function bind(string $abstract, $concrete = null, string $lifecycle = 'transient'): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'lifecycle' => $lifecycle,
            'shared' => in_array($lifecycle, ['singleton', 'per-request'])
        ];
    }

    /**
     * Bind a singleton service
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'singleton');
    }

    /**
     * Bind a per-request service
     * 
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function perRequest(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, 'per-request');
    }

    /**
     * Bind an instance
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a service from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Check if we have a direct instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if we have a singleton instance
        if (isset($this->bindings[$abstract]) && 
            $this->bindings[$abstract]['lifecycle'] === 'singleton' && 
            isset($this->singletonInstances[$abstract])) {
            return $this->singletonInstances[$abstract];
        }

        // Check if we have a per-request instance
        if (isset($this->bindings[$abstract]) && 
            $this->bindings[$abstract]['lifecycle'] === 'per-request' && 
            isset($this->requestInstances[$abstract])) {
            return $this->requestInstances[$abstract];
        }

        // Build the instance
        $instance = $this->build($abstract, $parameters);

        // Store instance based on lifecycle
        if (isset($this->bindings[$abstract])) {
            $lifecycle = $this->bindings[$abstract]['lifecycle'];
            
            if ($lifecycle === 'singleton') {
                $this->singletonInstances[$abstract] = $instance;
            } elseif ($lifecycle === 'per-request') {
                $this->requestInstances[$abstract] = $instance;
            }
        }

        return $instance;
    }

    /**
     * Build an instance of the given type
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    protected function build(string $abstract, array $parameters = [])
    {
        // If we have a binding, use it
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract]['concrete'];
            
            if (is_callable($concrete)) {
                return $concrete($this, $parameters);
            }
            
            $abstract = $concrete;
        }

        // Try to resolve the class
        try {
            $reflector = new ReflectionClass($abstract);
        } catch (\ReflectionException $e) {
            throw new RuntimeException("Class [{$abstract}] not found.");
        }

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class [{$abstract}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $abstract;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve dependencies for a method
     * 
     * @param array $parameters
     * @param array $primitives
     * @return array
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif ($dependency && !$dependency->isBuiltin()) {
                $dependencies[] = $this->make($dependency->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Unable to resolve dependency [{$parameter}] in class [{$parameter->getDeclaringClass()->getName()}]");
            }
        }

        return $dependencies;
    }

    /**
     * Check if a service is bound
     * 
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Get all bindings
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Clear all instances (useful for testing)
     * 
     * @return void
     */
    public function clearInstances(): void
    {
        $this->singletonInstances = [];
        $this->requestInstances = [];
    }

    /**
     * Start a new request (clears per-request instances)
     * 
     * @return void
     */
    public function startNewRequest(): void
    {
        $this->requestId = uniqid('request_', true);
        $this->requestInstances = [];
    }

    /**
     * Get current request ID
     * 
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
