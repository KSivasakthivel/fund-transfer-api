<?php

namespace App\Service\Transfer;

use App\Entity\Account;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class AccountLocker implements AccountLockerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Fetch account with pessimistic write lock
     *
     * @throws \DomainException When account not found
     */
    public function getAccountWithLock(string $accountNumber): Account
    {
        $account = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Account::class, 'a')
            ->where('a.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        if (!$account) {
            throw new \DomainException("Account not found: {$accountNumber}");
        }

        return $account;
    }

    /**
     * Fetch multiple accounts with pessimistic write lock
     *
     * @return array{source: Account, destination: Account}
     * @throws \DomainException When any account not found
     */
    public function getAccountsWithLock(string $sourceAccountNumber, string $destinationAccountNumber): array
    {
        $sourceAccount = $this->getAccountWithLock($sourceAccountNumber);
        $destinationAccount = $this->getAccountWithLock($destinationAccountNumber);

        return [
            'source' => $sourceAccount,
            'destination' => $destinationAccount,
        ];
    }
}
