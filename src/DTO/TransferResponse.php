<?php

namespace App\DTO;

class TransferResponse
{
    public function __construct(
        public string $referenceNumber,
        public string $status,
        public string $sourceAccountNumber,
        public string $destinationAccountNumber,
        public string $amount,
        public string $currency,
        public ?string $description,
        public string $createdAt,
        public ?string $completedAt = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'referenceNumber' => $this->referenceNumber,
            'status' => $this->status,
            'sourceAccountNumber' => $this->sourceAccountNumber,
            'destinationAccountNumber' => $this->destinationAccountNumber,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'createdAt' => $this->createdAt,
            'completedAt' => $this->completedAt,
        ];
    }
}
