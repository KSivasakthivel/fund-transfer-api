<?php

namespace App\Service\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ResponseBuilder
{
    public function success(mixed $data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ], $statusCode);
    }

    public function successWithCount(array $data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'count' => count($data),
        ], $statusCode);
    }

    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'error' => $message,
        ], $statusCode);
    }

    public function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return new JsonResponse([
            'error' => 'Validation failed',
            'details' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    public function serverError(string $message = 'An unexpected error occurred'): JsonResponse
    {
        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function unprocessableEntity(string $message): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
