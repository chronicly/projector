<?php
declare(strict_types=1);

namespace Chronhub\Projector\Repository;

use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository as Repository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Exception\QueryFailure;
use Chronhub\Projector\Exception\ProjectionAlreadyRunning;
use Chronhub\Projector\Exception\ProjectionNotFound;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Support\Event\ProjectorAttemptDeleted;
use Chronhub\Projector\Support\Event\ProjectorAttemptReset;
use Chronhub\Projector\Support\Event\ProjectorDeleted;
use Chronhub\Projector\Support\Event\ProjectorReset;
use Chronhub\Projector\Support\Event\ProjectorRestarted;
use Chronhub\Projector\Support\Event\ProjectorStarted;
use Chronhub\Projector\Support\Event\ProjectorStopped;
use Illuminate\Database\QueryException;

final class ProjectorRepository implements Repository
{
    public function __construct(private ProjectorContext $projectorContext,
                                private ProjectionProvider $projectionProvider,
                                private TimeLock $lock,
                                private JsonEncoder $jsonEncoder,
                                private string $streamName)
    {
    }

    public function prepare(?ReadModel $readModel): void
    {
        $this->projectorContext->runner()->stop(false);

        if (!$this->isProjectionExists()) {
            $this->create();
        }

        $this->acquireLock();

        if ($readModel && !$readModel->isInitialized()) {
            $readModel->initialize();
        }

        $this->projectorContext->position()->make($this->projectorContext->streamsNames());

        $this->loadState();

        event(new ProjectorStarted($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function stop(): void
    {
        $this->persist();

        $this->projectorContext->runner()->stop(true);
        $idleProjection = ProjectionStatus::IDLE();

        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'status' => $idleProjection->getValue()
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to stop projection for stream name: $this->streamName"
            );
        }

        $this->projectorContext->setStatus($idleProjection);

        event(new ProjectorStopped($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function startAgain(): void
    {
        $this->projectorContext->runner()->stop(false);
        $runningStatus = ProjectionStatus::RUNNING();
        $this->lock->acquire();

        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'status' => $runningStatus->ofValue(),
                'locked_until' => $this->lock->current(),
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to start projection again for stream name: $this->streamName"
            );
        }

        $this->projectorContext->setStatus($runningStatus);

        event(new ProjectorRestarted($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function persist(): void
    {
        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'position' => $this->encodeData($this->projectorContext->position()->all()),
                'state' => $this->encodeData($this->projectorContext->state()->getState()),
                'locked_until' => $this->lock->refresh(),
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to persist projection for stream name: $this->streamName"
            );
        }
    }

    public function reset(): void
    {
        event(new ProjectorAttemptReset($this->streamName, $this->projectorContext->state()->getState()));

        $this->projectorContext->position()->reset();

        $this->projectorContext->resetStateWithInitialize();

        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'position' => $this->encodeData($this->projectorContext->position()->all()),
                'state' => $this->encodeData($this->projectorContext->state()->getState()),
                'status' => $this->projectorContext->status()->ofValue()
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to reset projection for stream name: $this->streamName"
            );
        }

        event(new ProjectorReset($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function delete(bool $withEmittedEvents): callable
    {
        event(new ProjectorAttemptDeleted($this->streamName, $this->projectorContext->state()->getState(), $withEmittedEvents));

        try {
            $result = $this->projectionProvider->deleteByName($this->streamName);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to delete projection for stream name: $this->streamName"
            );
        }

        $context = $this->projectorContext;
        $streamName = $this->streamName;

        return function () use ($context, $streamName, $withEmittedEvents): void {
            $context->runner()->stop(true);

            $context->resetStateWithInitialize();

            $context->position()->reset();

            event(new ProjectorDeleted($streamName, $context->state()->getState(), $withEmittedEvents));
        };
    }

    public function loadState(): void
    {
        $result = $this->projectionProvider->findByName($this->streamName);

        if (!$result) {
            $exceptionMessage = "Projection not found with stream name $this->streamName\n";
            $exceptionMessage .= 'Did you call prepareExecution first on Projector lock instance?';

            throw new ProjectionNotFound($exceptionMessage);
        }

        $this->projectorContext->position()->merge(
            $this->jsonEncoder->decode($result->position())
        );

        if (!empty($state = $this->jsonEncoder->decode($result->state()))) {
            $this->projectorContext->state()->setState($state);
        }
    }

    public function loadStatus(): ProjectionStatus
    {
        $result = $this->projectionProvider->findByName($this->streamName);

        if (!$result) {
            return ProjectionStatus::RUNNING();
        }

        return ProjectionStatus::byValue($result->status());
    }

    public function isProjectionExists(): bool
    {
        return $this->projectionProvider->projectionExists($this->streamName);
    }

    public function acquireLock(): void
    {
        $runningProjection = ProjectionStatus::RUNNING();
        $this->lock->acquire();

        try {
            $result = $this->projectionProvider->acquireLock(
                $this->streamName,
                $runningProjection->ofValue(),
                $this->lock->current(),
                $this->lock->lastLockUpdate()->toString(),
            );
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new ProjectionAlreadyRunning(
                "Another projection process is already running for stream name: $this->streamName"
            );
        }

        $this->projectorContext->setStatus($runningProjection);
    }

    public function updateLock(): void
    {
        if ($this->lock->update()) {
            try {
                $result = $this->projectionProvider->updateProjection($this->streamName, [
                    'locked_until' => $this->lock->current(),
                    'position' => $this->encodeData($this->projectorContext->position()->all())
                ]);
            } catch (QueryException $queryException) {
                throw QueryFailure::fromQueryException($queryException);
            }

            if (!$result) {
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
            $this->projectionProvider->updateProjection($this->streamName, [
                'status' => $idleProjection->ofValue(),
                'locked_until' => null
            ]);
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        $this->projectorContext->setStatus($idleProjection);
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    private function create(): void
    {
        try {
            $result = $this->projectionProvider->createProjection(
                $this->streamName,
                $this->projectorContext->status()->ofValue()
            );
        } catch (QueryException $queryException) {
            throw QueryFailure::fromQueryException($queryException);
        }

        if (!$result) {
            throw new QueryFailure(
                "Unable to create projection for stream name: $this->streamName"
            );
        }
    }

    // todo check if Json Object can be used safely
    private function encodeData(array $data): string
    {
        if (count($data) > 0) {
            return $this->jsonEncoder->encode($data);
        }

        return '{}';
    }
}
