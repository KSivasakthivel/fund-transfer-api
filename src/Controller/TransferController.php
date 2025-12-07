<?php

namespace App\Controller;

use App\DTO\TransferRequest;
use App\DTO\TransferResponse;
use App\Service\FundTransferService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }

                return $this->json([
                    'error' => 'Validation failed',
                    'details' => $errorMessages,
                ], Response::HTTP_BAD_REQUEST);
            }

            $transaction = $this->transferService->transfer(
                $transferRequest->sourceAccountNumber,
                $transferRequest->destinationAccountNumber,
                $transferRequest->amount,
                $transferRequest->description
            );

            $response = new TransferResponse(
                $transaction->getReferenceNumber(),
                $transaction->getStatus(),
                $transaction->getSourceAccount()->getAccountNumber(),
                $transaction->getDestinationAccount()->getAccountNumber(),
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getDescription(),
                $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                $transaction->getCompletedAt()?->format('Y-m-d H:i:s')
            );

            return $this->json([
                'success' => true,
                'data' => $response->toArray(),
            ], Response::HTTP_CREATED);

        } catch (\DomainException $e) {
            $this->logger->warning('Transfer request failed', [
                'error' => $e->getMessage(),
                'request' => $request->getContent(),
            ]);

            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Exception $e) {
            $this->logger->error('Transfer request error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{referenceNumber}', name: 'get', methods: ['GET'])]
    public function getTransfer(string $referenceNumber): JsonResponse
    {
        try {
            $transaction = $this->transferService->getTransaction($referenceNumber);

            if (!$transaction) {
                return $this->json([
                    'error' => 'Transaction not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $response = new TransferResponse(
                $transaction->getReferenceNumber(),
                $transaction->getStatus(),
                $transaction->getSourceAccount()->getAccountNumber(),
                $transaction->getDestinationAccount()->getAccountNumber(),
                $transaction->getAmount(),
                $transaction->getCurrency(),
                $transaction->getDescription(),
                $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                $transaction->getCompletedAt()?->format('Y-m-d H:i:s')
            );

            return $this->json([
                'success' => true,
                'data' => $response->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Get transfer error', [
                'reference' => $referenceNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/account/{accountNumber}', name: 'list_by_account', methods: ['GET'])]
    public function getAccountTransfers(string $accountNumber, Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query->get('limit', 50);
            $limit = min(max($limit, 1), 100); // Between 1 and 100

            $transactions = $this->transferService->getAccountTransactions($accountNumber, $limit);

            $data = array_map(function ($transaction) {
                return (new TransferResponse(
                    $transaction->getReferenceNumber(),
                    $transaction->getStatus(),
                    $transaction->getSourceAccount()->getAccountNumber(),
                    $transaction->getDestinationAccount()->getAccountNumber(),
                    $transaction->getAmount(),
                    $transaction->getCurrency(),
                    $transaction->getDescription(),
                    $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
                    $transaction->getCompletedAt()?->format('Y-m-d H:i:s')
                ))->toArray();
            }, $transactions);

            return $this->json([
                'success' => true,
                'data' => $data,
                'count' => count($data),
            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            $this->logger->error('Get account transfers error', [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'An unexpected error occurred',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
