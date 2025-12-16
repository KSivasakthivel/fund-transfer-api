<?php

namespace App\Controller;

use App\Service\Contract\AccountServiceContract;
use App\Service\Contract\TransferServiceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/discovery', name: 'api_discovery_')]
class ServiceDiscoveryController extends AbstractController
{
    public function __construct(
        private TransferServiceContract $transferContract,
        private AccountServiceContract $accountContract
    ) {
    }

    /**
     * Get service information and available endpoints
     */
    #[Route('', name: 'info', methods: ['GET'])]
    public function serviceInfo(): JsonResponse
    {
        return $this->json([
            'service' => 'fund-transfer-api',
            'version' => '1.0.0',
            'description' => 'Microservice for fund transfers between accounts',
            'baseUrl' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            'services' => [
                $this->transferContract->getServiceName() => [
                    'version' => $this->transferContract->getServiceVersion(),
                    'capabilities' => $this->transferContract->getCapabilities(),
                    'endpoints' => [
                        'POST /api/v1/transfers' => 'Create a new transfer',
                        'GET /api/v1/transfers/{referenceNumber}' => 'Get transfer by reference',
                        'GET /api/v1/transfers/account/{accountNumber}' => 'List account transfers',
                    ],
                ],
                $this->accountContract->getServiceName() => [
                    'version' => $this->accountContract->getServiceVersion(),
                    'capabilities' => $this->accountContract->getCapabilities(),
                    'endpoints' => [
                        'GET /api/v1/accounts' => 'List all accounts',
                        'GET /api/v1/accounts/{accountNumber}' => 'Get account details',
                        'GET /api/v1/accounts/{accountNumber}/balance' => 'Get account balance',
                    ],
                ],
            ],
            'healthChecks' => [
                'liveness' => 'GET /health/live',
                'readiness' => 'GET /health/ready',
                'health' => 'GET /health',
            ],
        ]);
    }

    /**
     * Get OpenAPI/Swagger specification
     */
    #[Route('/openapi', name: 'openapi', methods: ['GET'])]
    public function openApiSpec(): JsonResponse
    {
        return $this->json([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Fund Transfer API',
                'version' => '1.0.0',
                'description' => 'Microservice API for fund transfers',
            ],
            'servers' => [
                [
                    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
                    'description' => 'API Server',
                ],
            ],
            'paths' => $this->getOpenApiPaths(),
        ]);
    }

    private function getOpenApiPaths(): array
    {
        return [
            '/api/v1/transfers' => [
                'post' => [
                    'summary' => 'Create a new fund transfer',
                    'tags' => ['Transfers'],
                    'operationId' => 'createTransfer',
                    'responses' => [
                        '201' => ['description' => 'Transfer created successfully'],
                        '400' => ['description' => 'Validation error'],
                        '422' => ['description' => 'Business rule violation'],
                    ],
                ],
            ],
            '/health' => [
                'get' => [
                    'summary' => 'Health check endpoint',
                    'tags' => ['Health'],
                    'operationId' => 'healthCheck',
                    'responses' => [
                        '200' => ['description' => 'Service is healthy'],
                        '503' => ['description' => 'Service is unhealthy'],
                    ],
                ],
            ],
        ];
    }
}
