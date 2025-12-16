<?php

namespace App\Service\Resilience;

use Psr\Log\LoggerInterface;

class CircuitBreaker implements CircuitBreakerInterface
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    private const FAILURE_THRESHOLD = 5;
    private const TIMEOUT_SECONDS = 60;
    
    private array $circuits = [];

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function execute(callable $operation, string $serviceName)
    {
        $this->initializeCircuit($serviceName);
        
        if ($this->isOpen($serviceName)) {
            $this->logger->warning('Circuit breaker is open', ['service' => $serviceName]);
            throw new \RuntimeException("Circuit breaker is open for service: {$serviceName}");
        }

        try {
            $result = $operation();
            $this->recordSuccess($serviceName);
            return $result;
            
        } catch (\Exception $e) {
            $this->recordFailure($serviceName);
            throw $e;
        }
    }

    public function isOpen(string $serviceName): bool
    {
        $this->initializeCircuit($serviceName);
        $circuit = $this->circuits[$serviceName];

        if ($circuit['state'] === self::STATE_CLOSED) {
            return false;
        }

        // Check if timeout has passed for half-open state
        if ($circuit['state'] === self::STATE_OPEN) {
            $timeSinceLastFailure = time() - ($circuit['lastFailure'] ?? 0);
            
            if ($timeSinceLastFailure >= self::TIMEOUT_SECONDS) {
                $this->circuits[$serviceName]['state'] = self::STATE_HALF_OPEN;
                $this->logger->info('Circuit breaker half-open', ['service' => $serviceName]);
                return false;
            }
        }

        return $circuit['state'] === self::STATE_OPEN;
    }

    public function getState(string $serviceName): array
    {
        $this->initializeCircuit($serviceName);
        return $this->circuits[$serviceName];
    }

    private function initializeCircuit(string $serviceName): void
    {
        if (!isset($this->circuits[$serviceName])) {
            $this->circuits[$serviceName] = [
                'state' => self::STATE_CLOSED,
                'failures' => 0,
                'lastFailure' => null,
            ];
        }
    }

    private function recordSuccess(string $serviceName): void
    {
        $this->circuits[$serviceName]['failures'] = 0;
        $this->circuits[$serviceName]['state'] = self::STATE_CLOSED;
        
        $this->logger->debug('Circuit breaker closed', ['service' => $serviceName]);
    }

    private function recordFailure(string $serviceName): void
    {
        $this->circuits[$serviceName]['failures']++;
        $this->circuits[$serviceName]['lastFailure'] = time();

        if ($this->circuits[$serviceName]['failures'] >= self::FAILURE_THRESHOLD) {
            $this->circuits[$serviceName]['state'] = self::STATE_OPEN;
            
            $this->logger->error('Circuit breaker opened', [
                'service' => $serviceName,
                'failures' => $this->circuits[$serviceName]['failures'],
            ]);
        }
    }
}
