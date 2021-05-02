<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\ProjectionStatus;

trait HasRemoteProjectionStatus
{
    public function __construct(protected ProjectorRepository $repository)
    {
    }

    protected function stopOnLoadingRemoteStatus(bool $keepRunning): bool
    {
        return $this->discoverRemoteProjectionStatus(true, $keepRunning);
    }

    protected function loadRemoteStatus(bool $keepRunning): void
    {
        $this->discoverRemoteProjectionStatus(false, $keepRunning);
    }

    private function discoverRemoteProjectionStatus(bool $firstExecution, bool $keepRunning): bool
    {
        return match ($this->repository->loadStatus()) {
            ProjectionStatus::STOPPING() => $this->markAsStop($firstExecution),
            ProjectionStatus::RESETTING() => $this->markAsReset($firstExecution, $keepRunning),
            ProjectionStatus::DELETING() => $this->markAsDelete($firstExecution, false),
            ProjectionStatus::DELETING_EMITTED_EVENTS() => $this->markAsDelete($firstExecution, true),
            default => false
        };
    }

    private function markAsStop(bool $firstExecution): bool
    {
        if ($firstExecution) {
            $this->repository->loadState();
        }

        $this->repository->stop();

        return $firstExecution;
    }

    private function markAsReset(bool $firstExecution, bool $keepRunning): bool
    {
        $this->repository->reset();

        if (!$firstExecution && $keepRunning) {
            $this->repository->startAgain();
        }

        return false;
    }

    private function markAsDelete(bool $firstExecution, bool $withEmittedEvents): bool
    {
        $this->repository->delete($withEmittedEvents);

        return $firstExecution;
    }
}
