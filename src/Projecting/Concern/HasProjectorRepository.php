<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Concern;

use Chronhub\Contracts\Projecting\ProjectionStatus;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use DateTimeImmutable;

trait HasProjectorRepository
{
    protected ProjectorRepository $repository;

    public function create(): void
    {
        $this->repository->create();
    }

    public function loadState(): void
    {
        $this->repository->loadState();
    }

    public function stop(): void
    {
        $this->repository->stop();
    }

    public function startAgain(): void
    {
        $this->repository->startAgain();
    }

    public function isProjectionExists(): bool
    {
        return $this->repository->isProjectionExists();
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->repository->loadStatus();
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

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        return $this->repository->shouldUpdateLock($dateTime);
    }

    public function getStreamName(): string
    {
        return $this->repository->getStreamName();
    }
}
