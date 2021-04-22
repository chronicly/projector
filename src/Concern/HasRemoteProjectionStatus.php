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

    protected function stopOnLoadingRemoteStatus(bool $firstExecution, bool $keepRunning): bool
    {
        return match ($this->repository->loadStatus()) {
            ProjectionStatus::STOPPING() => $this->stop($firstExecution),
            ProjectionStatus::RESETTING() => $this->reset($firstExecution, $keepRunning),
            ProjectionStatus::DELETING() => $this->delete($firstExecution, false),
            ProjectionStatus::DELETING_EMITTED_EVENTS() => $this->delete($firstExecution, true),
            default => false
        };
    }

    private function stop(bool $firstExecution): bool
    {
        if ($firstExecution) {
            $this->repository->loadState();
        }

        $this->repository->stop();

        return $firstExecution;
    }

    private function reset(bool $firstExecution, bool $keepRunning): bool
    {
        $this->repository->reset();

        if (!$firstExecution && $keepRunning) {
            $this->repository->startAgain();
        }

        return false;
    }

    private function delete(bool $firstExecution, bool $withEmittedEvents): bool
    {
        $this->repository->delete($withEmittedEvents);

        return $firstExecution;
    }
}
