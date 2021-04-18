<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Foundation\Exception\QueryFailure;
use Chronhub\Projector\Factory\ProjectionStatus;
use Illuminate\Database\QueryException;

trait HasWriteProjectorManager
{
    protected ProjectionProvider $projectionProvider;

    public function stop(string $streamName): void
    {
        $this->updateProjectionStatus($streamName, ProjectionStatus::STOPPING());
    }

    public function reset(string $streamName): void
    {
        $this->updateProjectionStatus($streamName, ProjectionStatus::RESETTING());
    }

    public function delete(string $streamName, bool $deleteEmittedEvents): void
    {
        $deleteProjectionStatus = $deleteEmittedEvents
            ? ProjectionStatus::DELETING_EMITTED_EVENTS()
            : ProjectionStatus::DELETING();

        $this->updateProjectionStatus($streamName, $deleteProjectionStatus);
    }

    private function updateProjectionStatus(string $streamName, ProjectionStatus $projectionStatus): void
    {
        try {
            $success = $this->projectionProvider->updateProjection(
                $streamName,
                ['status' => $projectionStatus->ofValue()]
            );
        } catch (QueryException $exception) {
            throw QueryFailure::fromQueryException($exception);
        }

        if (!$success) {
            $this->assertProjectionNameExists($streamName);
        }
    }
}
