<?php

namespace App\Service\Transfer;

use App\Entity\Account;

class TransferValidator implements TransferValidatorInterface
{
    private const MAX_TRANSFER_AMOUNT = '1000000.00';

    public function validateTransferRequest(
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

        if (bccomp($amount, self::MAX_TRANSFER_AMOUNT, 2) > 0) {
            throw new \DomainException('Transfer amount exceeds maximum limit');
        }
    }

    public function validateAccounts(
        Account $sourceAccount,
        Account $destinationAccount,
        string $amount
    ): void {
        $this->validateAccountStatus($sourceAccount, 'Source');
        $this->validateAccountStatus($destinationAccount, 'Destination');
        $this->validateCurrency($sourceAccount, $destinationAccount);
        $this->validateSufficientFunds($sourceAccount, $amount);
    }

    private function validateAccountStatus(Account $account, string $accountType): void
    {
        if (!$account->isActive()) {
            throw new \DomainException("{$accountType} account is not active");
        }
    }

    private function validateCurrency(Account $sourceAccount, Account $destinationAccount): void
    {
        if ($sourceAccount->getCurrency() !== $destinationAccount->getCurrency()) {
            throw new \DomainException('Currency mismatch between accounts');
        }
    }

    private function validateSufficientFunds(Account $account, string $amount): void
    {
        if (bccomp($account->getBalance(), $amount, 2) < 0) {
            throw new \DomainException('Insufficient funds in source account');
        }
    }
}
