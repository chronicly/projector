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

    protected function processOnStatus(bool $onFirstProcessing, bool $keepRunning): bool
    {
        switch ($this->repository->loadStatus()) {
            case ProjectionStatus::STOPPING():
                if ($onFirstProcessing) {
                    $this->repository->loadState();
                }

                $this->repository->stop();

                return $onFirstProcessing;
            case ProjectionStatus::DELETING():
                $this->repository->delete(false);

                return $onFirstProcessing;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->repository->delete(true);

                return $onFirstProcessing;
            case ProjectionStatus::RESETTING():
                $this->repository->reset();

                if (!$onFirstProcessing && $keepRunning) {
                    $this->repository->startAgain();
                }

                return false;
            default:
                return false;
        }
    }
}
