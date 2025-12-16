<?php

namespace App\Service\Health;

class HealthCheckAggregator
{
    /**
     * @var HealthCheckInterface[]
     */
    private array $healthChecks = [];

    public function addHealthCheck(HealthCheckInterface $healthCheck): void
    {
        $this->healthChecks[$healthCheck->getName()] = $healthCheck;
    }

    /**
     * Perform all health checks
     * 
     * @return array{status: string, timestamp: string, services: array, healthy: bool}
     */
    public function checkAll(): array
    {
        $services = [];
        $allHealthy = true;

        foreach ($this->healthChecks as $name => $healthCheck) {
            $result = $healthCheck->check();
            $services[$name] = $result;

            if ($result['status'] !== 'healthy') {
                $allHealthy = false;
            }
        }

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => date('c'),
            'services' => $services,
            'healthy' => $allHealthy,
        ];
    }

    /**
     * Check if service is ready to accept traffic
     */
    public function isReady(): bool
    {
        $result = $this->checkAll();
        return $result['healthy'];
    }
}
