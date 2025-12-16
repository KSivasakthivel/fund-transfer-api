<?php

namespace App\Service\Health;

use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RedisHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private RedisClient $redis,
        private LoggerInterface $logger
    ) {
    }

    public function check(): array
    {
        try {
            $this->redis->ping();
            
            return [
                'status' => 'healthy',
                'message' => 'Redis connection is active',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Redis health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    public function getName(): string
    {
        return 'redis';
    }
}
