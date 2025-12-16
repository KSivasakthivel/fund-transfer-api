<?php

namespace App\EventListener;

use App\Event\TransferCompletedEvent;
use App\Service\CacheService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransferCompletedListener implements EventSubscriberInterface
{
    public function __construct(
        private CacheService $cacheService,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TransferCompletedEvent::NAME => [
                ['invalidateCache', 10],
                ['logTransfer', 0],
            ],
        ];
    }

    public function invalidateCache(TransferCompletedEvent $event): void
    {
        $this->cacheService->invalidateAccountCache($event->getSourceAccountNumber());
        $this->cacheService->invalidateAccountCache($event->getDestinationAccountNumber());
    }

    public function logTransfer(TransferCompletedEvent $event): void
    {
        $this->logger->info('Fund transfer completed', [
            'reference' => $event->getReferenceNumber(),
            'source' => $event->getSourceAccountNumber(),
            'destination' => $event->getDestinationAccountNumber(),
            'amount' => $event->getAmount(),
        ]);
    }
}
