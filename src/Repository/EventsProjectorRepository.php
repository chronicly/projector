<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use Chronhub\Contracts\Projecting\ProjectionStatus;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Support\Event\ProjectorAttemptDeleted;
use Chronhub\Projector\Support\Event\ProjectorAttemptReset;
use Chronhub\Projector\Support\Event\ProjectorDeleted;
use Chronhub\Projector\Support\Event\ProjectorFailed;
use Chronhub\Projector\Support\Event\ProjectorReset;
use Chronhub\Projector\Support\Event\ProjectorRestarted;
use Chronhub\Projector\Support\Event\ProjectorStarted;
use Chronhub\Projector\Support\Event\ProjectorStopped;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final class EventsProjectorRepository implements ProjectorRepository
{
    public function __construct(private ProjectorRepository $projection,
                                private Dispatcher $eventDispatcher)
    {
    }

    private function handleRepositoryOperation(callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(
                new ProjectorFailed($this->projection->getStreamName(), $exception)
            );

            throw $exception;
        }
    }

    public function initiate(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->projection->initiate();

            $this->eventDispatcher->dispatch(new ProjectorStarted($this->projection->getStreamName()));
        });
    }

    public function stop(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->projection->stop();

            $this->eventDispatcher->dispatch(new ProjectorStopped($this->projection->getStreamName()));
        });
    }

    public function reset(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->eventDispatcher->dispatch(new ProjectorAttemptReset($this->projection->getStreamName()));

            $this->projection->reset();

            $this->eventDispatcher->dispatch(new ProjectorReset($this->projection->getStreamName()));
        });
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->handleRepositoryOperation(function () use ($withEmittedEvents) {
            $this->eventDispatcher->dispatch(new ProjectorAttemptDeleted($this->projection->getStreamName(), $withEmittedEvents));

            $this->projection->delete($withEmittedEvents);

            $this->eventDispatcher->dispatch(new ProjectorDeleted($this->projection->getStreamName(), $withEmittedEvents));
        });
    }

    public function startAgain(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->projection->startAgain();

            $this->eventDispatcher->dispatch(new ProjectorRestarted($this->projection->getStreamName()));
        });
    }

    public function loadState(): void
    {
        $this->projection->loadState();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->projection->loadStatus();
    }

    public function persist(): void
    {
        $this->projection->persist();
    }

    public function exists(): bool
    {
        return $this->projection->exists();
    }

    public function acquireLock(): void
    {
        $this->projection->acquireLock();
    }

    public function updateLock(): void
    {
        $this->projection->updateLock();
    }

    public function releaseLock(): void
    {
        $this->projection->releaseLock();
    }

    public function getStreamName(): string
    {
        return $this->projection->getStreamName();
    }
}
