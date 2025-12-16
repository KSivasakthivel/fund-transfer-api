<?php

namespace App\Service\Health;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {
    }

    public function check(): array
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection is active',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getName(): string
    {
        return 'database';
    }
}
