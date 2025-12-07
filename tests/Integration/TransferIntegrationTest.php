<?php

namespace App\Tests\Integration;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferIntegrationTest extends WebTestCase
{
    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::ensureKernelShutdown();
    }

    public function testSuccessfulTransfer(): void
    {
        // Setup test data
        $this->setupTestData();
        
        $client = static::createClient();

        $transferData = [
            'sourceAccountNumber' => 'ACC0000001',
            'destinationAccountNumber' => 'ACC0000002',
            'amount' => '250.00',
            'description' => 'Test transfer',
        ];

        $client->request(
            'POST',
            '/api/v1/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('completed', $response['data']['status']);
        $this->assertEquals('250.00', $response['data']['amount']);
        $this->assertNotEmpty($response['data']['referenceNumber']);

        // Verify balances were updated
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $entityManager->clear();
        
        $sourceAccount = $entityManager->getRepository(Account::class)
            ->findOneBy(['accountNumber' => 'ACC0000001']);
        $destinationAccount = $entityManager->getRepository(Account::class)
            ->findOneBy(['accountNumber' => 'ACC0000002']);

        $this->assertEquals('750.00', $sourceAccount->getBalance());
        $this->assertEquals('1250.00', $destinationAccount->getBalance());
    }

    public function testTransferWithInsufficientFunds(): void
    {
        $this->setupTestData();
        
        $client = static::createClient();

        $transferData = [
            'sourceAccountNumber' => 'ACC0000001',
            'destinationAccountNumber' => 'ACC0000002',
            'amount' => '2000.00',
            'description' => 'Transfer exceeding balance',
        ];

        $client->request(
            'POST',
            '/api/v1/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Insufficient funds', $response['error']);
    }

    public function testTransferWithInvalidAccountNumber(): void
    {
        $this->setupTestData();
        
        $client = static::createClient();

        $transferData = [
            'sourceAccountNumber' => 'INVALID123',
            'destinationAccountNumber' => 'ACC0000002',
            'amount' => '100.00',
        ];

        $client->request(
            'POST',
            '/api/v1/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetTransactionByReferenceNumber(): void
    {
        $this->setupTestData();
        
        $client = static::createClient();

        // First create a transfer
        $transferData = [
            'sourceAccountNumber' => 'ACC0000001',
            'destinationAccountNumber' => 'ACC0000002',
            'amount' => '100.00',
        ];

        $client->request(
            'POST',
            '/api/v1/transfers',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($transferData)
        );

        $createResponse = json_decode($client->getResponse()->getContent(), true);
        $referenceNumber = $createResponse['data']['referenceNumber'];

        // Now get the transaction
        $client->request('GET', '/api/v1/transfers/' . $referenceNumber);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals($referenceNumber, $response['data']['referenceNumber']);
    }

    public function testGetAccountBalance(): void
    {
        $this->setupTestData();
        
        $client = static::createClient();

        $client->request('GET', '/api/v1/accounts/ACC0000001/balance');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('1000.00', $response['data']['balance']);
    }

    public function testGetAccount(): void
    {
        $this->setupTestData();
        
        $client = static::createClient();

        $client->request('GET', '/api/v1/accounts/ACC0000001');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('ACC0000001', $response['data']['accountNumber']);
        $this->assertEquals('Alice Johnson', $response['data']['holderName']);
    }

    public function testHealthCheck(): void
    {
        $client = static::createClient();

        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $response['status']);
    }

    private function setupTestData(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        $this->cleanDatabase($entityManager);
        $this->createTestAccounts($entityManager);
        
        $entityManager->close();
        self::ensureKernelShutdown();
    }

    private function cleanDatabase(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();
        
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE transactions');
        $connection->executeStatement('TRUNCATE TABLE accounts');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function createTestAccounts(EntityManagerInterface $entityManager): void
    {
        $account1 = new Account();
        $account1->setAccountNumber('ACC0000001');
        $account1->setHolderName('Alice Johnson');
        $account1->setBalance('1000.00');
        $account1->setCurrency('USD');
        $account1->setStatus('active');

        $account2 = new Account();
        $account2->setAccountNumber('ACC0000002');
        $account2->setHolderName('Bob Smith');
        $account2->setBalance('1000.00');
        $account2->setCurrency('USD');
        $account2->setStatus('active');

        $entityManager->persist($account1);
        $entityManager->persist($account2);
        $entityManager->flush();
    }
}
