<?php

namespace App\Controller;

use App\Service\Health\HealthCheckAggregator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private HealthCheckAggregator $healthCheckAggregator
    ) {
    }

    /**
     * Liveness probe - checks if service is running
     */
    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function liveness(): JsonResponse
    {
        return $this->json([
            'status' => 'alive',
            'timestamp' => date('c'),
            'service' => 'fund-transfer-api',
            'version' => '1.0.0',
        ]);
    }

    /**
     * Readiness probe - checks if service is ready to accept traffic
     */
    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function readiness(): JsonResponse
    {
        $result = $this->healthCheckAggregator->checkAll();
        $statusCode = $result['healthy'] ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return $this->json($result, $statusCode);
    }

    /**
     * Detailed health check with all dependencies
     */
    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $result = $this->healthCheckAggregator->checkAll();
        $statusCode = $result['healthy'] ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return $this->json([
            ...$result,
            'service' => 'fund-transfer-api',
            'version' => '1.0.0',
        ], $statusCode);
    }
}

