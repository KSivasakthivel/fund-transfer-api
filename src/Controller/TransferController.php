<?php

namespace App\Controller;

use App\DTO\TransferRequest;
use App\Service\FundTransferService;
use App\Service\Response\DtoMapper;
use App\Service\Response\ResponseBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/transfers', name: 'api_transfers_')]
class TransferController extends AbstractController
{
    public function __construct(
        private FundTransferService $transferService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ResponseBuilder $responseBuilder,
        private DtoMapper $dtoMapper,
        private LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createTransfer(Request $request): JsonResponse
    {
        try {
            $transferRequest = $this->serializer->deserialize(
                $request->getContent(),
                TransferRequest::class,
                'json'
            );

            $errors = $this->validator->validate($transferRequest);
            
            if (count($errors) > 0) {
                return $this->responseBuilder->validationError($errors);
            }

            $transaction = $this->transferService->transfer(
                $transferRequest->sourceAccountNumber,
                $transferRequest->destinationAccountNumber,
                $transferRequest->amount,
                $transferRequest->description
            );

            $response = $this->dtoMapper->mapTransactionToResponse($transaction);

            return $this->responseBuilder->success($response->toArray(), 201);

        } catch (\DomainException $e) {
            $this->logger->warning('Transfer request failed', [
                'error' => $e->getMessage(),
                'request' => $request->getContent(),
            ]);

            return $this->responseBuilder->unprocessableEntity($e->getMessage());

        } catch (\Exception $e) {
            $this->logger->error('Transfer request error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }

    #[Route('/{referenceNumber}', name: 'get', methods: ['GET'])]
    public function getTransfer(string $referenceNumber): JsonResponse
    {
        try {
            $transaction = $this->transferService->getTransaction($referenceNumber);

            if (!$transaction) {
                return $this->responseBuilder->notFound('Transaction not found');
            }

            $response = $this->dtoMapper->mapTransactionToResponse($transaction);

            return $this->responseBuilder->success($response->toArray());

        } catch (\Exception $e) {
            $this->logger->error('Get transfer error', [
                'reference' => $referenceNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }

    #[Route('/account/{accountNumber}', name: 'list_by_account', methods: ['GET'])]
    public function getAccountTransfers(string $accountNumber, Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query->get('limit', 50);
            $limit = min(max($limit, 1), 100); // Between 1 and 100

            $transactions = $this->transferService->getAccountTransactions($accountNumber, $limit);
            $data = $this->dtoMapper->mapTransactionsToArray($transactions);

            return $this->responseBuilder->successWithCount($data);

        } catch (\DomainException $e) {
            return $this->responseBuilder->notFound($e->getMessage());

        } catch (\Exception $e) {
            $this->logger->error('Get account transfers error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->responseBuilder->serverError();
        }
    }
}
