<?php

namespace App\Service\Transfer;

use App\Entity\Account;

interface AccountLockerInterface
{
    /**
     * Fetch account with pessimistic write lock
     *
     * @throws \DomainException When account not found
     */
    public function getAccountWithLock(string $accountNumber): Account;

    /**
     * Fetch multiple accounts with pessimistic write lock
     *
     * @return array{source: Account, destination: Account}
     * @throws \DomainException When any account not found
     */
    public function getAccountsWithLock(string $sourceAccountNumber, string $destinationAccountNumber): array;
}
