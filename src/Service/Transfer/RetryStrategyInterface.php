<?php

namespace App\Service\Transfer;

interface RetryStrategyInterface
{
    /**
     * Execute a callable with retry logic for lock timeouts
     *
     * @template T
     * @param callable(): T $operation
     * @param array<string, mixed> $context
     * @return T
     * @throws \RuntimeException When max retries exceeded
     */
    public function executeWithRetry(callable $operation, array $context = []);
}
