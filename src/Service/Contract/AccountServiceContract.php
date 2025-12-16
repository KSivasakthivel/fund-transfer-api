<?php

namespace App\Service\Contract;

/**
 * Account Service Contract
 * Defines the public interface for account operations
 */
class AccountServiceContract implements ServiceContractInterface
{
    public function getServiceName(): string
    {
        return 'account-service';
    }

    public function getServiceVersion(): string
    {
        return '1.0.0';
    }

    public function getCapabilities(): array
    {
        return [
            'getAccount' => [
                'description' => 'Retrieve account by account number',
                'input' => [
                    'accountNumber' => 'string',
                ],
                'output' => [
                    'account' => 'Account|null',
                ],
            ],
            'getBalance' => [
                'description' => 'Get current balance for an account',
                'input' => [
                    'accountNumber' => 'string',
                ],
                'output' => [
                    'balance' => 'decimal(15,2)',
                    'currency' => 'string',
                ],
            ],
            'listAccounts' => [
                'description' => 'List all active accounts',
                'input' => [],
                'output' => [
                    'accounts' => 'Account[]',
                ],
            ],
        ];
    }
}
