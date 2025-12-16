<?php

namespace App\Service\Transfer;

use App\Entity\Account;

interface TransferValidatorInterface
{
    /**
     * Validate transfer request parameters
     *
     * @throws \DomainException When validation fails
     */
    public function validateTransferRequest(
        string $sourceAccountNumber,
        string $destinationAccountNumber,
        string $amount
    ): void;

    /**
     * Validate account states and transfer feasibility
     *
     * @throws \DomainException When validation fails
     */
    public function validateAccounts(
        Account $sourceAccount,
        Account $destinationAccount,
        string $amount
    ): void;
}
