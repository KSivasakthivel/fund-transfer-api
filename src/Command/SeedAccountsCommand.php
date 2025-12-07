<?php

namespace App\Command;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-accounts',
    description: 'Seed test accounts for development and testing',
)]
class SeedAccountsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accounts = [
            [
                'accountNumber' => 'ACC1000000001',
                'holderName' => 'Alice Johnson',
                'balance' => '5000.00',
                'currency' => 'USD',
            ],
            [
                'accountNumber' => 'ACC1000000002',
                'holderName' => 'Bob Smith',
                'balance' => '3000.00',
                'currency' => 'USD',
            ],
            [
                'accountNumber' => 'ACC1000000003',
                'holderName' => 'Charlie Brown',
                'balance' => '10000.00',
                'currency' => 'USD',
            ],
            [
                'accountNumber' => 'ACC1000000004',
                'holderName' => 'Diana Prince',
                'balance' => '2500.00',
                'currency' => 'USD',
            ],
            [
                'accountNumber' => 'ACC1000000005',
                'holderName' => 'Eve Anderson',
                'balance' => '7500.00',
                'currency' => 'USD',
            ],
        ];

        foreach ($accounts as $accountData) {
            $account = new Account();
            $account->setAccountNumber($accountData['accountNumber']);
            $account->setHolderName($accountData['holderName']);
            $account->setBalance($accountData['balance']);
            $account->setCurrency($accountData['currency']);
            $account->setStatus('active');

            $this->entityManager->persist($account);

            $io->info(sprintf(
                'Created account %s for %s with balance %s %s',
                $accountData['accountNumber'],
                $accountData['holderName'],
                $accountData['balance'],
                $accountData['currency']
            ));
        }

        $this->entityManager->flush();

        $io->success('Test accounts seeded successfully!');

        return Command::SUCCESS;
    }
}
