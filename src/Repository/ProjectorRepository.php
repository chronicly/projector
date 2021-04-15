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
use Chronhub\Projector\Support\Event\ProjectorDeleted;
use Chronhub\Projector\Support\Event\ProjectorReset;
use Chronhub\Projector\Support\Event\ProjectorRestarted;
use Chronhub\Projector\Support\Event\ProjectorStarted;
use Chronhub\Projector\Support\Event\ProjectorStopped;
use Chronhub\Projector\Support\LockTime;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Database\QueryException;

final class ProjectorRepository implements Repository
{
    private ?DateTimeImmutable $lastLockUpdate = null;

    public function __construct(private ProjectorContext $projectorContext,
                                private ProjectionProvider $projectionProvider,
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

    // todo remove from interface
    // should only be access from prepare
    public function create(): void
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
        $now = LockTime::fromNow();

        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'status' => $runningStatus->ofValue(),
                'locked_until' => $this->createLockUntilString($now)
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

        $this->lastLockUpdate = $now->toDateTime();

        event(new ProjectorRestarted($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function persist(): void
    {
        try {
            $result = $this->projectionProvider->updateProjection($this->streamName, [
                'position' => $this->encodeData($this->projectorContext->position()->all()),
                'state' => $this->encodeData($this->projectorContext->state()->getState()),
                'locked_until' => $this->createLockUntilString(LockTime::fromNow())
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
                "Unable to reset projection for stream name: {$this->streamName}"
            );
        }

        event(new ProjectorReset($this->streamName, $this->projectorContext->state()->getState()));
    }

    public function delete(bool $withEmittedEvents): callable
    {
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
        $now = LockTime::fromNow();
        $lockUntil = $this->createLockUntilString($now);
        $runningProjection = ProjectionStatus::RUNNING();

        try {
            $result = $this->projectionProvider->acquireLock(
                $this->streamName,
                $runningProjection->ofValue(),
                $lockUntil,
                $now->toString()
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

        $this->lastLockUpdate = $now->toDateTime();
    }

    public function updateLock(): void
    {
        $now = LockTime::fromNow();

        if ($this->shouldUpdateLock($now->toDateTime())) {
            $lockedUntil = $this->createLockUntilString($now);

            try {
                $result = $this->projectionProvider->updateProjection($this->streamName, [
                    'locked_until' => $lockedUntil,
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

            $this->lastLockUpdate = $now->toDateTime();
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

    public function shouldUpdateLock(DateTimeImmutable $dateTime): bool
    {
        $threshold = $this->projectorContext->option()->updateLockThreshold();

        if (null === $this->lastLockUpdate || 0 === $threshold) {
            return true;
        }

        $updateLockThreshold = new DateInterval(sprintf('PT%sS', floor($threshold / 1000)));

        $updateLockThreshold->f = ($threshold % 1000) / 1000;

        $threshold = $this->lastLockUpdate->add($updateLockThreshold);

        return $threshold <= $dateTime;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    private function createLockUntilString(LockTime $dateTime): string
    {
        return $dateTime->createLockUntil(
            $this->projectorContext->option()->lockTimoutMs()
        );
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
