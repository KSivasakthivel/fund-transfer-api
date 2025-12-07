<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    public function testAccountCreation(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setHolderName('John Doe');
        $account->setBalance('1000.00');
        $account->setCurrency('USD');
        $account->setStatus('active');

        $this->assertEquals('1234567890', $account->getAccountNumber());
        $this->assertEquals('John Doe', $account->getHolderName());
        $this->assertEquals('1000.00', $account->getBalance());
        $this->assertEquals('USD', $account->getCurrency());
        $this->assertEquals('active', $account->getStatus());
        $this->assertTrue($account->isActive());
    }

    public function testDebitSuccess(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setBalance('1000.00');
        $account->setStatus('active');

        $account->debit('300.00');

        $this->assertEquals('700.00', $account->getBalance());
    }

    public function testDebitInsufficientFunds(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setBalance('100.00');
        $account->setStatus('active');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient funds');

        $account->debit('200.00');
    }

    public function testDebitInactiveAccount(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setBalance('1000.00');
        $account->setStatus('suspended');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot debit inactive account');

        $account->debit('100.00');
    }

    public function testCreditSuccess(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setBalance('1000.00');
        $account->setStatus('active');

        $account->credit('500.00');

        $this->assertEquals('1500.00', $account->getBalance());
    }

    public function testCreditInactiveAccount(): void
    {
        $account = new Account();
        $account->setAccountNumber('1234567890');
        $account->setBalance('1000.00');
        $account->setStatus('closed');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot credit inactive account');

        $account->credit('100.00');
    }

    public function testIsActiveWithDifferentStatuses(): void
    {
        $account = new Account();

        $account->setStatus('active');
        $this->assertTrue($account->isActive());

        $account->setStatus('suspended');
        $this->assertFalse($account->isActive());

        $account->setStatus('closed');
        $this->assertFalse($account->isActive());
    }
}
