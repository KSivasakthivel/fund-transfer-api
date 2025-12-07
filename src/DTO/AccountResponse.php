<?php

namespace App\DTO;

class AccountResponse
{
    public function __construct(
        public string $accountNumber,
        public string $holderName,
        public string $balance,
        public string $currency,
        public string $status,
        public string $createdAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'accountNumber' => $this->accountNumber,
            'holderName' => $this->holderName,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
