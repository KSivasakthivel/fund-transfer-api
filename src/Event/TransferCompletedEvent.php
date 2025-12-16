<?php

namespace App\Event;

use App\Entity\Transaction;
use Symfony\Contracts\EventDispatcher\Event;

class TransferCompletedEvent extends Event
{
    public const NAME = 'transfer.completed';

    public function __construct(
        private Transaction $transaction
    ) {
    }

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function getSourceAccountNumber(): string
    {
        return $this->transaction->getSourceAccount()->getAccountNumber();
    }

    public function getDestinationAccountNumber(): string
    {
        return $this->transaction->getDestinationAccount()->getAccountNumber();
    }

    public function getAmount(): string
    {
        return $this->transaction->getAmount();
    }

    public function getReferenceNumber(): string
    {
        return $this->transaction->getReferenceNumber();
    }
}
