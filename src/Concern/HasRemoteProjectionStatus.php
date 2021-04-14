<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Support\Event\ProjectorDeleted;
use Chronhub\Projector\Support\Event\ProjectorReset;
use Chronhub\Projector\Support\Event\ProjectorRestarted;
use Chronhub\Projector\Support\Event\ProjectorStarted;
use Chronhub\Projector\Support\Event\ProjectorStopped;

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

                event(new ProjectorStopped($this->projector->getStreamName(), $this->projector->getState()));

                return $onFirstProcessing;
            case ProjectionStatus::DELETING():
                $this->projector->delete(false);

                event(new ProjectorDeleted($this->projector->getStreamName(), $this->projector->getState(), false));

                return $onFirstProcessing;
            case ProjectionStatus::DELETING_EMITTED_EVENTS():
                $this->projector->delete(true);

                event(new ProjectorDeleted($this->projector->getStreamName(), $this->projector->getState(), true));

                return $onFirstProcessing;
            case ProjectionStatus::RESETTING():
                $this->projector->reset();

                event(new ProjectorReset($this->projector->getStreamName(), $this->projector->getState()));

                if (!$onFirstProcessing && $keepRunning) {
                    $this->repository->startAgain();

                    event(new ProjectorRestarted($this->projector->getStreamName(), $this->projector->getState()));
                }

                return false;
            default:
                event(new ProjectorStarted($this->projector->getStreamName(), $this->projector->getState()));

                return false;
        }
    }
}
