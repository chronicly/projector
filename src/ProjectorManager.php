<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorManager as Manager;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\ProjectorRepository as Repository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Exception\QueryFailure;
use Chronhub\Projector\Concern\HasReadProjectorManager;
use Chronhub\Projector\Context\Context;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\EventCounter;
use Chronhub\Projector\Factory\InMemoryState;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Factory\StreamCache;
use Chronhub\Projector\Factory\StreamPosition;
use Chronhub\Projector\Repository\ProjectionRepository;
use Chronhub\Projector\Repository\ProjectorRepository;
use Chronhub\Projector\Repository\ReadModelRepository;
use Chronhub\Projector\Repository\TimeLock;
use Chronhub\Projector\Support\Projector\Option\ConstructableProjectorOption;
use Illuminate\Database\QueryException;

final class ProjectorManager implements Manager
{
    use HasReadProjectorManager;

    public function __construct(private Chronicler $chronicler,
                                private EventStreamProvider $eventStreamProvider,
                                protected ProjectionProvider $projectionProvider,
                                private MessageAlias $messageAlias,
                                private ProjectionQueryScope $projectionQueryScope,
                                protected JsonEncoder $jsonEncoder,
                                private Clock $clock,
                                private ProjectorOption|array $options = [])
    {
    }

    public function createQuery(array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext(
            $this->newProjectorOption($options),
            null,
            null
        );

        return new ProjectQuery($context, $this->chronicler, $this->messageAlias);
    }

    public function createProjection(string $streamName, array $options = []): ProjectorFactory
    {
        $options = $this->newProjectorOption($options);

        $context = $this->newProjectorContext(
            $options,
            new EventCounter(),
            new StreamCache($options->streamCacheSize())
        );

        $repository = new ProjectionRepository(
            $this->newProjectorRepository($streamName, $context),
            $this->chronicler
        );

        return new ProjectProjection(
            $context, $repository, $this->chronicler, $this->messageAlias, $streamName
        );
    }

    public function createReadModelProjection(string $streamName,
                                              ReadModel $readModel,
                                              array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext(
            $this->newProjectorOption($options),
            new EventCounter(),
            null
        );

        $repository = new ReadModelRepository(
            $this->newProjectorRepository($streamName, $context),
            $readModel
        );

        return new ProjectReadModel(
            $context, $repository, $this->chronicler, $this->messageAlias, $streamName, $readModel
        );
    }

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

    public function queryScope(): ProjectionQueryScope
    {
        return $this->projectionQueryScope;
    }

    private function updateProjectionStatus(string $streamName, ProjectionStatus $projectionStatus): void
    {
        try {
            $result = $this->projectionProvider->updateProjection(
                $streamName,
                ['status' => $projectionStatus->ofValue()]
            );
        } catch (QueryException $exception) {
            throw QueryFailure::fromQueryException($exception);
        }

        if (!$result) {
            $this->assertProjectionNameExists($streamName);
        }
    }

    private function newProjectorRepository(string $streamName, ProjectorContext $context): Repository
    {
        $projectorLock = new TimeLock(
            $this->clock,
            $context->option()->lockTimoutMs(),
            $context->option()->updateLockThreshold()
        );

        return new ProjectorRepository(
            $context,
            $this->projectionProvider,
            $projectorLock,
            $this->jsonEncoder,
            $streamName
        );
    }

    private function newProjectorOption(array $options): ProjectorOption
    {
        if (is_array($this->options)) {
            $options = array_merge($this->options, $options);

            return new ConstructableProjectorOption(...$options);
        }

        if (count($options) > 0) {
            throw new RuntimeException("Projector options can not be overridden");
        }

        return $this->options;
    }

    private function newProjectorContext(ProjectorOption $option,
                                         ?EventCounter $eventCounter,
                                         ?StreamCache $streamCache): ProjectorContext
    {
        $streamPosition = new StreamPosition(
            $this->eventStreamProvider,
            $this->clock,
            $option->retriesMs(),
            $option->detectionWindows()
        );

        return new Context(
            $option,
            $streamPosition,
            new InMemoryState(),
            ProjectionStatus::IDLE(),
            $this->clock,
            $this->messageAlias,
            $eventCounter,
            $streamCache
        );
    }
}
