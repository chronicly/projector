<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Projector\Context\ProjectorContext;

final class PersistOrUpdateLock
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if ($context->position->gapDetected()) {
            $this->handleDetectedGap($context->position);
        } else {
            $this->handleCounterIsReached($context);
        }

        $context->eventCounter->reset();

        return $next($context);
    }

    private function handleDetectedGap(StreamPosition $streamPosition): void
    {
        $streamPosition->sleepWithGapDetected();

        $this->repository->persist();

        $streamPosition->setGapDetected(false);
    }

    private function handleCounterIsReached(ProjectorContext $context): void
    {
        $context->position->resetRetries();

        $context->eventCounter->isReset()
            ? $this->sleepBeforeUpdateLock($context->option->sleep())
            : $this->repository->persist();
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep($sleep);

        $this->repository->updateLock();
    }
}
