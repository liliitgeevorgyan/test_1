<?php

namespace App\Examples;

/**
 * User service that demonstrates dependency injection
 */
class UserService
{
    private LoggerInterface $logger;
    private DatabaseConnection $db;
    private string $serviceName;

    public function __construct(
        LoggerInterface $logger,
        DatabaseConnection $db,
        string $serviceName = 'UserService'
    ) {
        $this->logger = $logger;
        $this->db = $db;
        $this->serviceName = $serviceName;
    }

    public function createUser(string $username, string $email): array
    {
        $this->logger->log("Creating user: {$username} with email: {$email}");
        
        // Simulate user creation
        $user = [
            'id' => rand(1, 1000),
            'username' => $username,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->logger->log("User created successfully with ID: {$user['id']}");
        
        return $user;
    }

    public function getUser(int $id): ?array
    {
        $this->logger->log("Fetching user with ID: {$id}");
        
        // Simulate user fetch
        return [
            'id' => $id,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'created_at' => '2024-01-01 12:00:00'
        ];
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getDatabaseInfo(): array
    {
        return [
            'host' => $this->db->getHost(),
            'database' => $this->db->getDatabase()
        ];
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
