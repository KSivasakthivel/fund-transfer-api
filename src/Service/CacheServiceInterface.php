<?php

namespace App\Service;

use App\Entity\Account;

interface CacheServiceInterface
{
    /**
     * Get account from cache or database
     */
    public function getAccount(string $accountNumber): ?Account;

    /**
     * Get account balance from cache
     */
    public function getAccountBalance(string $accountNumber): ?string;

    /**
     * Invalidate account cache
     */
    public function invalidateAccountCache(string $accountNumber): void;

    /**
     * Set account in cache
     */
    public function setAccount(Account $account): void;

    /**
     * Clear all account caches
     */
    public function clearAllAccountCaches(): void;
}
