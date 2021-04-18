<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Model\ProjectionModel;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Exception\QueryFailure;
use Chronhub\Projector\Exception\ProjectionAlreadyRunning;
use Chronhub\Projector\Exception\ProjectionNotFound;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Repository\TimeLock;
use Illuminate\Database\QueryException;

trait HasProjectorRepository
{
    protected ProjectionProvider $provider;
    protected ProjectorContext $context;
    protected TimeLock $timer;
    protected JsonEncoder $jsonEncoder;
    protected string $streamName;

    public function loadState(): void
    {
        $projection = $this->provider->findByName($this->streamName);

        if (!$projection instanceof ProjectionModel) {
            $exceptionMessage = "Projection not found with stream name $this->streamName\n";
            $exceptionMessage .= 'Did you call initiate first on Projector instance?';

            throw new ProjectionNotFound($exceptionMessage);
        }

        $this->context->position()->merge(
            $this->jsonEncoder->decode($projection->position())
        );

        $state = $this->jsonEncoder->decode($projection->state());

        if (is_array($state) && count($state) > 0) {
            $this->context->state()->setState($state);
        }
    }

    public function stop(): void
    {
        $this->persist();

        $this->context->runner()->stop(true);
        $idleProjection = ProjectionStatus::IDLE();

        try {
            $success = $this->provider->updateProjection($this->streamName, [
                'status' => $idleProjection->getValue()
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to stop projection for stream name: $this->streamName"
            );
        }

        $this->context->setStatus($idleProjection);
    }

    public function startAgain(): void
    {
        $this->context->runner()->stop(false);
        $runningStatus = ProjectionStatus::RUNNING();
        $this->timer->acquire();

        try {
            $success = $this->provider->updateProjection($this->streamName, [
                'status' => $runningStatus->ofValue(),
                'locked_until' => $this->timer->current(),
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to start projection again for stream name: $this->streamName"
            );
        }

        $this->context->setStatus($runningStatus);
    }

    public function isProjectionExists(): bool
    {
        return $this->provider->projectionExists($this->streamName);
    }

    public function loadStatus(): Status
    {
        $projection = $this->provider->findByName($this->streamName);

        if (!$projection instanceof ProjectionModel) {
            return ProjectionStatus::RUNNING();
        }

        return ProjectionStatus::byValue($projection->status());
    }

    public function acquireLock(): void
    {
        $runningProjection = ProjectionStatus::RUNNING();

        $this->timer->acquire();

        try {
            $success = $this->provider->acquireLock(
                $this->streamName,
                $runningProjection->ofValue(),
                $this->timer->current(),
                $this->timer->lastLockUpdate()->toString(),
            );
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            $message = "Acquiring lock failed for stream $this->streamName.\n";
            $message .= "Another projection process is already running or \n";
            $message .= "wait till the stopping process complete";

            throw new ProjectionAlreadyRunning($message);
        }

        $this->context->setStatus($runningProjection);
    }

    public function updateLock(): void
    {
        if ($this->timer->update()) {
            try {
                $success = $this->provider->updateProjection($this->streamName, [
                    'locked_until' => $this->timer->current(),
                    'position' => $this->encodeData($this->context->position()->all())
                ]);
            } catch (QueryException $queryException) {
                throw QueryFailure::fromQueryException($queryException);
            }

            if (!$success) {
                throw new QueryFailure(
                    "An error occurred when updating lock for stream name: $this->streamName"
                );
            }
        }
    }

    public function releaseLock(): void
    {
        $idleProjection = ProjectionStatus::IDLE();

        try {
            $this->provider->updateProjection($this->streamName, [
                'status' => $idleProjection->ofValue(),
                'locked_until' => null
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        $this->context->setStatus($idleProjection);
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    protected function createProjection(): void
    {
        try {
            $success = $this->provider->createProjection(
                $this->streamName,
                $this->context->status()->ofValue()
            );
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to create projection for stream name: $this->streamName"
            );
        }
    }

    protected function persistProjection(): void
    {
        try {
            $success = $this->provider->updateProjection($this->streamName, [
                'position' => $this->encodeData($this->context->position()->all()),
                'state' => $this->encodeData($this->context->state()->getState()),
                'locked_until' => $this->timer->refresh(),
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to persist projection for stream name: $this->streamName"
            );
        }
    }

    protected function resetProjection(): void
    {
        $this->context->position()->reset();

        $this->context->resetStateWithInitialize();

        try {
            $success = $this->provider->updateProjection($this->streamName, [
                'position' => $this->encodeData($this->context->position()->all()),
                'state' => $this->encodeData($this->context->state()->getState()),
                'status' => $this->context->status()->ofValue()
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to reset projection for stream name: $this->streamName"
            );
        }
    }

    protected function deleteProjection(): void
    {
        try {
            $success = $this->provider->deleteByName($this->streamName);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$success) {
            throw new QueryFailure(
                "Unable to delete projection for stream name: $this->streamName"
            );
        }

        $this->context->runner()->stop(true);

        $this->context->resetStateWithInitialize();

        $this->context->position()->reset();
    }

    private function encodeData(array $data): string
    {
        if (count($data) > 0) {
            return $this->jsonEncoder->encode($data);
        }

        return '{}';
    }
}
