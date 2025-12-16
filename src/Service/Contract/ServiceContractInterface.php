<?php

namespace App\Service\Contract;

/**
 * Service contract interface for microservices communication
 * All public-facing services should implement this for discoverability
 */
interface ServiceContractInterface
{
    /**
     * Get service name/identifier
     */
    public function getServiceName(): string;

    /**
     * Get service version
     */
    public function getServiceVersion(): string;

    /**
     * Get available operations/capabilities
     * 
     * @return array<string, array{description: string, input: array, output: array}>
     */
    public function getCapabilities(): array;
}
