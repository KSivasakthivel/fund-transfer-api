<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Event\TransferCompletedEvent;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\Transfer\AccountLockerInterface;
use App\Service\Transfer\RetryStrategyInterface;
use App\Service\Transfer\TransferValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FundTransferService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private TransactionRepository $transactionRepository,
        private TransferValidatorInterface $validator,
        private AccountLockerInterface $accountLocker,
        private RetryStrategyInterface $retryStrategy,
        private EventDispatcherInterface $eventDispatcher,
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
        $this->validator->validateTransferRequest($sourceAccountNumber, $destinationAccountNumber, $amount);

        $transaction = new Transaction();

        try {
            $result = $this->retryStrategy->executeWithRetry(
                fn() => $this->executeTransfer(
                    $sourceAccountNumber,
                    $destinationAccountNumber,
                    $amount,
                    $description,
                    $transaction
                ),
                [
                    'source' => $sourceAccountNumber,
                    'destination' => $destinationAccountNumber,
                    'amount' => $amount,
                ]
            );

            $this->eventDispatcher->dispatch(
                new TransferCompletedEvent($result),
                TransferCompletedEvent::NAME
            );

            return $result;

        } catch (\DomainException $e) {
            $this->logger->warning('Fund transfer failed due to business rule violation', [
                'source' => $sourceAccountNumber,
                'destination' => $destinationAccountNumber,
                'amount' => $amount,
                'reason' => $e->getMessage(),
            ]);
            throw $e;

        } catch (\Exception $e) {
            $this->logger->error('Fund transfer failed', [
                'source' => $sourceAccountNumber,
                'destination' => $destinationAccountNumber,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unexpected error during fund transfer: ' . $e->getMessage(), 0, $e);
        }
    }

    private function executeTransfer(
        string $sourceAccountNumber,
        string $destinationAccountNumber,
        string $amount,
        ?string $description,
        Transaction $transaction
    ): Transaction {
        $this->entityManager->beginTransaction();

        try {
            // Lock and fetch accounts
            $accounts = $this->accountLocker->getAccountsWithLock(
                $sourceAccountNumber,
                $destinationAccountNumber
            );
            $sourceAccount = $accounts['source'];
            $destinationAccount = $accounts['destination'];

            // Validate accounts
            $this->validator->validateAccounts($sourceAccount, $destinationAccount, $amount);

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

            return $transaction;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            if ($transaction->getId()) {
                $transaction->markAsFailed($e->getMessage());
                $this->transactionRepository->save($transaction);
                $this->entityManager->flush();
            }
            
            throw $e;
        }
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
}
