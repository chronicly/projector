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
            ProjectionStatus::DELETING() => $this->delete($firstExecution),
            ProjectionStatus::DELETING_EMITTED_EVENTS() => $this->deleteWithEvents($firstExecution),
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

    private function delete(bool $firstExecution): bool
    {
        $this->repository->delete(false);

        return $firstExecution;
    }

    private function deleteWithEvents(bool $firstExecution): bool
    {
        $this->repository->delete(true);

        return $firstExecution;
    }
}
