<?php

namespace App\Service\Response;

use App\DTO\AccountResponse;
use App\DTO\TransferResponse;
use App\Entity\Account;
use App\Entity\Transaction;

class DtoMapper
{
    public function mapTransactionToResponse(Transaction $transaction): TransferResponse
    {
        return new TransferResponse(
            $transaction->getReferenceNumber(),
            $transaction->getStatus(),
            $transaction->getSourceAccount()->getAccountNumber(),
            $transaction->getDestinationAccount()->getAccountNumber(),
            $transaction->getAmount(),
            $transaction->getCurrency(),
            $transaction->getDescription(),
            $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            $transaction->getCompletedAt()?->format('Y-m-d H:i:s')
        );
    }

    public function mapAccountToResponse(Account $account): AccountResponse
    {
        return new AccountResponse(
            $account->getAccountNumber(),
            $account->getHolderName(),
            $account->getBalance(),
            $account->getCurrency(),
            $account->getStatus(),
            $account->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param Transaction[] $transactions
     * @return array
     */
    public function mapTransactionsToArray(array $transactions): array
    {
        return array_map(
            fn(Transaction $transaction) => $this->mapTransactionToResponse($transaction)->toArray(),
            $transactions
        );
    }

    /**
     * @param Account[] $accounts
     * @return array
     */
    public function mapAccountsToArray(array $accounts): array
    {
        return array_map(
            fn(Account $account) => $this->mapAccountToResponse($account)->toArray(),
            $accounts
        );
    }
}
