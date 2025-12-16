<?php

namespace App\Controller;

use App\Repository\AccountRepository;
use App\Service\CacheServiceInterface;
use App\Service\Response\DtoMapper;
use App\Service\Response\ResponseBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/accounts', name: 'api_accounts_')]
class AccountController extends AbstractController
{
    public function __construct(
        private AccountRepository $accountRepository,
        private CacheServiceInterface $cacheService,
        private ResponseBuilder $responseBuilder,
        private DtoMapper $dtoMapper,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/{accountNumber}', name: 'get', methods: ['GET'])]
    public function getAccount(string $accountNumber): JsonResponse
    {
        try {
            $account = $this->cacheService->getAccount($accountNumber);

            if (!$account) {
                return $this->responseBuilder->notFound('Account not found');
            }

            $response = $this->dtoMapper->mapAccountToResponse($account);

            return $this->responseBuilder->success($response->toArray());

        } catch (\Exception $e) {
            $this->logger->error('Get account error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }

    #[Route('/{accountNumber}/balance', name: 'get_balance', methods: ['GET'])]
    public function getBalance(string $accountNumber): JsonResponse
    {
        try {
            $balance = $this->cacheService->getAccountBalance($accountNumber);

            if ($balance === null) {
                return $this->responseBuilder->notFound('Account not found');
            }

            return $this->responseBuilder->success([
                'accountNumber' => $accountNumber,
                'balance' => $balance,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Get balance error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function listAccounts(): JsonResponse
    {
        try {
            $accounts = $this->accountRepository->findActiveAccounts();
            $data = $this->dtoMapper->mapAccountsToArray($accounts);

            return $this->responseBuilder->successWithCount($data);

        } catch (\Exception $e) {
            $this->logger->error('List accounts error', [
                'error' => $e->getMessage(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }
}
