<?php

namespace App\Service\Observability;

interface CorrelationIdGeneratorInterface
{
    /**
     * Generate a unique correlation ID for request tracing
     */
    public function generate(): string;

    /**
     * Get current correlation ID
     */
    public function getCurrentId(): ?string;

    /**
     * Set correlation ID
     */
    public function setCurrentId(string $id): void;
}
