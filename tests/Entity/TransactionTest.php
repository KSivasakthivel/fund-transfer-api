<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use App\Entity\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testTransactionCreation(): void
    {
        $sourceAccount = new Account();
        $sourceAccount->setAccountNumber('1234567890');

        $destinationAccount = new Account();
        $destinationAccount->setAccountNumber('0987654321');

        $transaction = new Transaction();
        $transaction->setSourceAccount($sourceAccount);
        $transaction->setDestinationAccount($destinationAccount);
        $transaction->setAmount('500.00');
        $transaction->setCurrency('USD');
        $transaction->setDescription('Test transfer');

        $this->assertEquals('500.00', $transaction->getAmount());
        $this->assertEquals('USD', $transaction->getCurrency());
        $this->assertEquals('Test transfer', $transaction->getDescription());
        $this->assertEquals('pending', $transaction->getStatus());
        $this->assertNotEmpty($transaction->getReferenceNumber());
    }

    public function testMarkAsCompleted(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount('100.00');

        $this->assertEquals('pending', $transaction->getStatus());
        $this->assertNull($transaction->getCompletedAt());

        $transaction->markAsCompleted();

        $this->assertEquals('completed', $transaction->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getCompletedAt());
    }

    public function testMarkAsFailed(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount('100.00');

        $failureReason = 'Insufficient funds';
        $transaction->markAsFailed($failureReason);

        $this->assertEquals('failed', $transaction->getStatus());
        $this->assertEquals($failureReason, $transaction->getFailureReason());
        $this->assertInstanceOf(\DateTimeImmutable::class, $transaction->getCompletedAt());
    }

    public function testReferenceNumberFormat(): void
    {
        $transaction = new Transaction();
        $referenceNumber = $transaction->getReferenceNumber();

        $this->assertStringStartsWith('TXN', $referenceNumber);
        $this->assertGreaterThanOrEqual(20, strlen($referenceNumber));
        $this->assertLessThanOrEqual(24, strlen($referenceNumber));
    }
}
