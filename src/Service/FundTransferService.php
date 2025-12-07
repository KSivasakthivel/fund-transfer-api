<?php

namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FundTransferService
{
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private CacheService $cacheService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Transfer funds between two accounts with transaction integrity
     *
     * @throws \DomainException When business rules are violated
     * @throws \RuntimeException When transfer fails
     */
    public function transfer(
        string $sourceAccountNumber,
        string $destinationAccountNumber,
        string $amount,
        ?string $description = null
    ): Transaction {
        $this->validateTransferRequest($sourceAccountNumber, $destinationAccountNumber, $amount);

        $transaction = new Transaction();
        $retryCount = 0;

        while ($retryCount < self::MAX_RETRY_ATTEMPTS) {
            try {
                $this->entityManager->beginTransaction();

                // Lock and fetch accounts with pessimistic locking
                $sourceAccount = $this->getAccountWithLock($sourceAccountNumber);
                $destinationAccount = $this->getAccountWithLock($destinationAccountNumber);

                $this->validateAccounts($sourceAccount, $destinationAccount, $amount);

                // Create transaction record
                $transaction->setSourceAccount($sourceAccount);
                $transaction->setDestinationAccount($destinationAccount);
                $transaction->setAmount($amount);
                $transaction->setCurrency($sourceAccount->getCurrency());
                $transaction->setDescription($description);
                $transaction->setStatus('pending');

                $this->transactionRepository->save($transaction);

                // Perform the transfer
                $sourceAccount->debit($amount);
                $destinationAccount->credit($amount);

                $this->accountRepository->save($sourceAccount);
                $this->accountRepository->save($destinationAccount);

                // Mark transaction as completed
                $transaction->markAsCompleted();
                $this->transactionRepository->save($transaction);

                // Commit transaction
                $this->entityManager->flush();
                $this->entityManager->commit();

                // Invalidate cache
                $this->cacheService->invalidateAccountCache($sourceAccountNumber);
                $this->cacheService->invalidateAccountCache($destinationAccountNumber);

                $this->logger->info('Fund transfer completed', [
                    'reference' => $transaction->getReferenceNumber(),
                    'source' => $sourceAccountNumber,
                    'destination' => $destinationAccountNumber,
                    'amount' => $amount,
                ]);

                return $transaction;

            } catch (\Doctrine\DBAL\Exception\LockWaitTimeoutException $e) {
                $this->entityManager->rollback();
                $retryCount++;

                if ($retryCount >= self::MAX_RETRY_ATTEMPTS) {
                    $reason = 'Transfer failed after maximum retry attempts due to lock timeout';
                    $transaction->markAsFailed($reason);
                    $this->logger->error($reason, [
                        'source' => $sourceAccountNumber,
                        'destination' => $destinationAccountNumber,
                        'amount' => $amount,
                        'error' => $e->getMessage(),
                    ]);
                    throw new \RuntimeException($reason, 0, $e);
                }

                // Exponential backoff
                usleep(100000 * pow(2, $retryCount - 1));
                continue;

            } catch (\DomainException $e) {
                $this->entityManager->rollback();
                $transaction->markAsFailed($e->getMessage());
                
                $this->logger->warning('Fund transfer failed due to business rule violation', [
                    'source' => $sourceAccountNumber,
                    'destination' => $destinationAccountNumber,
                    'amount' => $amount,
                    'reason' => $e->getMessage(),
                ]);

                throw $e;

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $reason = 'Unexpected error during fund transfer: ' . $e->getMessage();
                $transaction->markAsFailed($reason);

                $this->logger->error('Fund transfer failed', [
                    'source' => $sourceAccountNumber,
                    'destination' => $destinationAccountNumber,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw new \RuntimeException($reason, 0, $e);
            }
        }

        throw new \RuntimeException('Transfer failed: Maximum retry attempts exceeded');
    }

    /**
     * Get transaction by reference number
     */
    public function getTransaction(string $referenceNumber): ?Transaction
    {
        return $this->transactionRepository->findByReferenceNumber($referenceNumber);
    }

    /**
     * Get transaction history for an account
     */
    public function getAccountTransactions(string $accountNumber, int $limit = 50): array
    {
        $account = $this->accountRepository->findByAccountNumber($accountNumber);
        
        if (!$account) {
            throw new \DomainException("Account not found: {$accountNumber}");
        }

        return $this->transactionRepository->findByAccount($account, $limit);
    }

    private function validateTransferRequest(
        string $sourceAccountNumber,
        string $destinationAccountNumber,
        string $amount
    ): void {
        if ($sourceAccountNumber === $destinationAccountNumber) {
            throw new \DomainException('Source and destination accounts cannot be the same');
        }

        if (bccomp($amount, '0', 2) <= 0) {
            throw new \DomainException('Transfer amount must be positive');
        }

        // Maximum transfer limit
        if (bccomp($amount, '1000000.00', 2) > 0) {
            throw new \DomainException('Transfer amount exceeds maximum limit');
        }
    }

    private function getAccountWithLock(string $accountNumber): Account
    {
        $account = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Account::class, 'a')
            ->where('a.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        if (!$account) {
            throw new \DomainException("Account not found: {$accountNumber}");
        }

        return $account;
    }

    private function validateAccounts(
        Account $sourceAccount,
        Account $destinationAccount,
        string $amount
    ): void {
        if (!$sourceAccount->isActive()) {
            throw new \DomainException('Source account is not active');
        }

        if (!$destinationAccount->isActive()) {
            throw new \DomainException('Destination account is not active');
        }

        if ($sourceAccount->getCurrency() !== $destinationAccount->getCurrency()) {
            throw new \DomainException('Currency mismatch between accounts');
        }

        if (bccomp($sourceAccount->getBalance(), $amount, 2) < 0) {
            throw new \DomainException('Insufficient funds in source account');
        }
    }
}
