<?php

namespace App\Service\Observability;

class CorrelationIdGenerator implements CorrelationIdGeneratorInterface
{
    private ?string $currentId = null;

    public function generate(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('YmdHis'),
            getmypid(),
            bin2hex(random_bytes(8))
        );
    }

    public function getCurrentId(): ?string
    {
        return $this->currentId;
    }

    public function setCurrentId(string $id): void
    {
        $this->currentId = $id;
    }
}
