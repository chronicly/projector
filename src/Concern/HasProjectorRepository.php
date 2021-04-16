<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\ProjectionStatus;
use Chronhub\Contracts\Projecting\ProjectorRepository;

trait HasProjectorRepository
{
    protected ProjectorRepository $repository;

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

    public function getStreamName(): string
    {
        return $this->repository->getStreamName();
    }
}
