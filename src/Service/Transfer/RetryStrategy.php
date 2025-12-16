<?php

namespace App\Service\Transfer;

use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Psr\Log\LoggerInterface;

class RetryStrategy implements RetryStrategyInterface
{
    private const MAX_RETRY_ATTEMPTS = 3;
    private const BASE_BACKOFF_MICROSECONDS = 100000;

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Execute a callable with retry logic for lock timeouts
     *
     * @template T
     * @param callable(): T $operation
     * @param array<string, mixed> $context
     * @return T
     * @throws \RuntimeException When max retries exceeded
     */
    public function executeWithRetry(callable $operation, array $context = [])
    {
        $retryCount = 0;
        $lastException = null;

        while ($retryCount < self::MAX_RETRY_ATTEMPTS) {
            try {
                return $operation();

            } catch (LockWaitTimeoutException $e) {
                $lastException = $e;
                $retryCount++;

                if ($retryCount >= self::MAX_RETRY_ATTEMPTS) {
                    $this->logger->error('Maximum retry attempts exceeded', array_merge($context, [
                        'retry_count' => $retryCount,
                        'error' => $e->getMessage(),
                    ]));
                    break;
                }

                $this->logger->info('Retrying operation after lock timeout', array_merge($context, [
                    'retry_count' => $retryCount,
                    'max_retries' => self::MAX_RETRY_ATTEMPTS,
                ]));

                $this->applyBackoff($retryCount);
            }
        }

        throw new \RuntimeException(
            'Transfer failed after maximum retry attempts due to lock timeout',
            0,
            $lastException
        );
    }

    private function applyBackoff(int $retryCount): void
    {
        $backoffTime = self::BASE_BACKOFF_MICROSECONDS * pow(2, $retryCount - 1);
        usleep((int) $backoffTime);
    }
}
