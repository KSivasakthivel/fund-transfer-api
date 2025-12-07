<?php

namespace App\Service;

use App\Entity\Account;
use App\Repository\AccountRepository;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class CacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const ACCOUNT_PREFIX = 'account:';
    private const ACCOUNT_BALANCE_PREFIX = 'account:balance:';

    public function __construct(
        private RedisClient $redis,
        private AccountRepository $accountRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Get account from cache or database
     */
    public function getAccount(string $accountNumber): ?Account
    {
        $cacheKey = self::ACCOUNT_PREFIX . $accountNumber;

        try {
            $cached = $this->redis->get($cacheKey);
            
            if ($cached) {
                $this->logger->debug('Account cache hit', ['account' => $accountNumber]);
                return unserialize($cached);
            }

            $this->logger->debug('Account cache miss', ['account' => $accountNumber]);
            
            $account = $this->accountRepository->findByAccountNumber($accountNumber);
            
            if ($account) {
                $this->redis->setex($cacheKey, self::CACHE_TTL, serialize($account));
            }

            return $account;

        } catch (\Exception $e) {
            $this->logger->warning('Cache get failed, falling back to database', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);
            
            return $this->accountRepository->findByAccountNumber($accountNumber);
        }
    }

    /**
     * Get account balance from cache
     */
    public function getAccountBalance(string $accountNumber): ?string
    {
        $cacheKey = self::ACCOUNT_BALANCE_PREFIX . $accountNumber;

        try {
            $balance = $this->redis->get($cacheKey);
            
            if ($balance !== null) {
                $this->logger->debug('Balance cache hit', ['account' => $accountNumber]);
                return $balance;
            }

            $account = $this->accountRepository->findByAccountNumber($accountNumber);
            
            if ($account) {
                $balance = $account->getBalance();
                $this->redis->setex($cacheKey, self::CACHE_TTL, $balance);
                return $balance;
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->warning('Cache get balance failed', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);
            
            $account = $this->accountRepository->findByAccountNumber($accountNumber);
            return $account?->getBalance();
        }
    }

    /**
     * Invalidate account cache
     */
    public function invalidateAccountCache(string $accountNumber): void
    {
        try {
            $this->redis->del([
                self::ACCOUNT_PREFIX . $accountNumber,
                self::ACCOUNT_BALANCE_PREFIX . $accountNumber,
            ]);

            $this->logger->debug('Account cache invalidated', ['account' => $accountNumber]);

        } catch (\Exception $e) {
            $this->logger->warning('Cache invalidation failed', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store account in cache
     */
    public function cacheAccount(Account $account): void
    {
        try {
            $cacheKey = self::ACCOUNT_PREFIX . $account->getAccountNumber();
            $this->redis->setex($cacheKey, self::CACHE_TTL, serialize($account));

            $balanceKey = self::ACCOUNT_BALANCE_PREFIX . $account->getAccountNumber();
            $this->redis->setex($balanceKey, self::CACHE_TTL, $account->getBalance());

        } catch (\Exception $e) {
            $this->logger->warning('Cache storage failed', [
                'account' => $account->getAccountNumber(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear all cache (useful for testing)
     */
    public function clearAll(): void
    {
        try {
            $this->redis->flushdb();
            $this->logger->info('All cache cleared');
        } catch (\Exception $e) {
            $this->logger->warning('Cache clear failed', ['error' => $e->getMessage()]);
        }
    }
}
