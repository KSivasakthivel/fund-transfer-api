<?php

namespace App\Service\Health;

interface HealthCheckInterface
{
    /**
     * Perform health check
     * 
     * @return array{status: string, message?: string, details?: array}
     */
    public function check(): array;

    /**
     * Get service name
     */
    public function getName(): string;
}
