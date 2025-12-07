<?php

namespace App\Controller;

use App\DTO\AccountResponse;
use App\Service\CacheService;
use App\Repository\AccountRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/accounts', name: 'api_accounts_')]
class AccountController extends AbstractController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private CacheService $cacheService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/{accountNumber}', name: 'get', methods: ['GET'])]
    public function getAccount(string $accountNumber): JsonResponse
    {
        try {
            $account = $this->cacheService->getAccount($accountNumber);

            if (!$account) {
                return $this->json([
                    'error' => 'Account not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $response = new AccountResponse(
                $account->getAccountNumber(),
                $account->getHolderName(),
                $account->getBalance(),
                $account->getCurrency(),
                $account->getStatus(),
                $account->getCreatedAt()->format('Y-m-d H:i:s')
            );

            return $this->json([
                'success' => true,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Get account error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{accountNumber}/balance', name: 'get_balance', methods: ['GET'])]
    public function getBalance(string $accountNumber): JsonResponse
    {
        try {
            $balance = $this->cacheService->getAccountBalance($accountNumber);

            if ($balance === null) {
                return $this->json([
                    'error' => 'Account not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'success' => true,
                'data' => [
                    'accountNumber' => $accountNumber,
                    'balance' => $balance,
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Get balance error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function listAccounts(): JsonResponse
    {
        try {
            $accounts = $this->accountRepository->findActiveAccounts();

            $data = array_map(function ($account) {
                return (new AccountResponse(
                    $account->getAccountNumber(),
                    $account->getHolderName(),
                    $account->getBalance(),
                    $account->getCurrency(),
                    $account->getStatus(),
                    $account->getCreatedAt()->format('Y-m-d H:i:s')
                ))->toArray();
            }, $accounts);

            return $this->json([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('List accounts error', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
