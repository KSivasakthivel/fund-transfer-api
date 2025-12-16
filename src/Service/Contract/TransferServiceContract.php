<?php

namespace App\Service\Contract;

/**
 * Transfer Service Contract
 * Defines the public interface for fund transfer operations
 */
class TransferServiceContract implements ServiceContractInterface
{
    public function getServiceName(): string
    {
        return 'fund-transfer-service';
    }

    public function getServiceVersion(): string
    {
        return '1.0.0';
    }

    public function getCapabilities(): array
    {
        return [
            'transfer' => [
                'description' => 'Transfer funds between two accounts',
                'input' => [
                    'sourceAccountNumber' => 'string',
                    'destinationAccountNumber' => 'string',
                    'amount' => 'decimal(15,2)',
                    'description' => 'string|null',
                ],
                'output' => [
                    'referenceNumber' => 'string',
                    'status' => 'string',
                    'amount' => 'decimal(15,2)',
                    'createdAt' => 'datetime',
                ],
            ],
            'getTransaction' => [
                'description' => 'Retrieve transaction by reference number',
                'input' => [
                    'referenceNumber' => 'string',
                ],
                'output' => [
                    'transaction' => 'Transaction|null',
                ],
            ],
            'getAccountTransactions' => [
                'description' => 'Get transaction history for an account',
                'input' => [
                    'accountNumber' => 'string',
                    'limit' => 'int',
                ],
                'output' => [
                    'transactions' => 'Transaction[]',
                ],
            ],
        ];
    }
}
