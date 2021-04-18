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
    public function __construct(private ProjectorRepository $repository,
                                private Dispatcher $eventDispatcher)
    {
    }

    private function handleRepositoryOperation(callable $operation): void
    {
        try {
            $operation();
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(
                new ProjectorFailed($this->repository->getStreamName(), $exception)
            );

            throw $exception;
        }
    }

    public function initiate(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->repository->initiate();

            $this->eventDispatcher->dispatch(new ProjectorStarted($this->repository->getStreamName()));
        });
    }

    public function stop(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->repository->stop();

            $this->eventDispatcher->dispatch(new ProjectorStopped($this->repository->getStreamName()));
        });
    }

    public function reset(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->eventDispatcher->dispatch(new ProjectorAttemptReset($this->repository->getStreamName()));

            $this->repository->reset();

            $this->eventDispatcher->dispatch(new ProjectorReset($this->repository->getStreamName()));
        });
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->handleRepositoryOperation(function () use ($withEmittedEvents) {
            $this->eventDispatcher->dispatch(new ProjectorAttemptDeleted($this->repository->getStreamName(), $withEmittedEvents));

            $this->repository->delete($withEmittedEvents);

            $this->eventDispatcher->dispatch(new ProjectorDeleted($this->repository->getStreamName(), $withEmittedEvents));
        });
    }

    public function startAgain(): void
    {
        $this->handleRepositoryOperation(function () {
            $this->repository->startAgain();

            $this->eventDispatcher->dispatch(new ProjectorRestarted($this->repository->getStreamName()));
        });
    }

    public function loadState(): void
    {
        $this->repository->loadState();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->repository->loadStatus();
    }

    public function persist(): void
    {
        $this->repository->persist();
    }

    public function isProjectionExists(): bool
    {
        return $this->repository->isProjectionExists();
    }

    public function acquireLock(): void
    {
        $this->repository->acquireLock();
    }

    public function updateLock(): void
    {
        $this->repository->updateLock();
    }

    public function releaseLock(): void
    {
        $this->repository->releaseLock();
    }

    public function getStreamName(): string
    {
        return $this->repository->getStreamName();
    }
}
