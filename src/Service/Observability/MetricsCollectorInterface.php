<?php

namespace App\Service\Observability;

interface MetricsCollectorInterface
{
    /**
     * Increment a counter metric
     */
    public function increment(string $metric, int $value = 1, array $tags = []): void;

    /**
     * Record a timing metric (in milliseconds)
     */
    public function timing(string $metric, float $duration, array $tags = []): void;

    /**
     * Record a gauge metric
     */
    public function gauge(string $metric, float $value, array $tags = []): void;

    /**
     * Get all collected metrics
     */
    public function getMetrics(): array;
}
