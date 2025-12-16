<?php

namespace App\Tests\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\FundTransferService;
use App\Service\Transfer\AccountLocker;
use App\Service\Transfer\RetryStrategy;
use App\Service\Transfer\TransferValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FundTransferServiceTest extends TestCase
{
    private FundTransferService $service;
    private EntityManagerInterface $entityManager;
    private AccountRepository $accountRepository;
    private TransactionRepository $transactionRepository;
    private TransferValidator $validator;
    private AccountLocker $accountLocker;
    private RetryStrategy $retryStrategy;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->validator = $this->createMock(TransferValidator::class);
        $this->accountLocker = $this->createMock(AccountLocker::class);
        $this->retryStrategy = $this->createMock(RetryStrategy::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new FundTransferService(
            $this->entityManager,
            $this->accountRepository,
            $this->transactionRepository,
            $this->validator,
            $this->accountLocker,
            $this->retryStrategy,
            $this->eventDispatcher,
            $this->logger
        );
    }

    public function testTransferValidationSameAccount(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Source and destination accounts cannot be the same');

        $this->validator
            ->expects($this->once())
            ->method('validateTransferRequest')
            ->willThrowException(new \DomainException('Source and destination accounts cannot be the same'));

        $this->service->transfer('1234567890', '1234567890', '100.00');
    }

    public function testTransferValidationNegativeAmount(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transfer amount must be positive');

        $this->validator
            ->expects($this->once())
            ->method('validateTransferRequest')
            ->willThrowException(new \DomainException('Transfer amount must be positive'));

        $this->service->transfer('1234567890', '0987654321', '-100.00');
    }

    public function testTransferValidationZeroAmount(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transfer amount must be positive');

        $this->validator
            ->expects($this->once())
            ->method('validateTransferRequest')
            ->willThrowException(new \DomainException('Transfer amount must be positive'));

        $this->service->transfer('1234567890', '0987654321', '0.00');
    }

    public function testTransferValidationMaximumLimit(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transfer amount exceeds maximum limit');

        $this->validator
            ->expects($this->once())
            ->method('validateTransferRequest')
            ->willThrowException(new \DomainException('Transfer amount exceeds maximum limit'));

        $this->service->transfer('1234567890', '0987654321', '1000001.00');
    }

    public function testGetTransactionByReferenceNumber(): void
    {
        $referenceNumber = 'TXN20231201120000ABC123';
        $transaction = new Transaction();

        $this->transactionRepository
            ->expects($this->once())
            ->method('findByReferenceNumber')
            ->with($referenceNumber)
            ->willReturn($transaction);

        $result = $this->service->getTransaction($referenceNumber);

        $this->assertSame($transaction, $result);
    }

    public function testGetAccountTransactionsAccountNotFound(): void
    {
        $accountNumber = '1234567890';

        $this->accountRepository
            ->expects($this->once())
            ->method('findByAccountNumber')
            ->with($accountNumber)
            ->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Account not found');

        $this->service->getAccountTransactions($accountNumber);
    }
}
