<?php

namespace App\Service\Resilience;

interface CircuitBreakerInterface
{
    /**
     * Execute a callable with circuit breaker protection
     * 
     * @template T
     * @param callable(): T $operation
     * @param string $serviceName
     * @return T
     * @throws \RuntimeException When circuit is open
     */
    public function execute(callable $operation, string $serviceName);

    /**
     * Check if circuit is open for a service
     */
    public function isOpen(string $serviceName): bool;

    /**
     * Get circuit state
     * 
     * @return array{state: string, failures: int, lastFailure: ?int}
     */
    public function getState(string $serviceName): array;
}
