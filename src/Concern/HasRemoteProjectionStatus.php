<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\ProjectionStatus;

trait HasRemoteProjectionStatus
{
    public function __construct(protected PersistentProjector $projector,
                                protected ProjectorRepository $repository)
    {
    }

    protected function processOnStatus(bool $onFirstProcessing, bool $keepRunning): bool
    {
        switch ($this->repository->loadStatus()) {
            case ProjectionStatus::STOPPING():
                if ($onFirstProcessing) {
                    $this->repository->loadState();
                }

                $this->projector->stop();

                return $onFirstProcessing;
            case ProjectionStatus::DELETING():
                $this->projector->delete(false);

                return $onFirstProcessing;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->projector->delete(true);

                return $onFirstProcessing;
            case ProjectionStatus::RESETTING():
                $this->projector->reset();

                if (!$onFirstProcessing && $keepRunning) {
                    $this->repository->startAgain();
                }

                return false;
            default:
                return false;
        }
    }
}
