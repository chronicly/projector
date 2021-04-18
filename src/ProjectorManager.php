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
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Chronhub\Contracts\Support\JsonEncoder;
use Chronhub\Foundation\Exception\QueryFailure;
use Chronhub\Projector\Concern\HasReadProjectorManager;
use Chronhub\Projector\Context\Context;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\EventCounter;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Factory\StreamCache;
use Chronhub\Projector\Factory\StreamPosition;
use Chronhub\Projector\Repository\EventsProjectorRepository;
use Chronhub\Projector\Repository\ProjectionRepository;
use Chronhub\Projector\Repository\ReadModelRepository;
use Chronhub\Projector\Repository\TimeLock;
use Chronhub\Projector\Support\Option\ConstructableProjectorOption;
use Illuminate\Contracts\Events\Dispatcher;
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
                                private ?Dispatcher $eventDispatcher = null,
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

        $repository = $this->newProjectorRepository($context, $streamName, null);

        return new ProjectProjection(
            $context, $repository, $this->chronicler, $this->messageAlias, $streamName
        );
    }

    public function createReadModelProjection(string $streamName,
                                              ReadModel $readModel,
                                              array $options = []): ProjectorFactory
    {
        $options = $this->newProjectorOption($options);

        $context = $this->newProjectorContext($options, new EventCounter(), null);

        $repository = $this->newProjectorRepository($context, $streamName, $readModel);

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

    private function newTimeLock(ProjectorContext $context): TimeLock
    {
        return new TimeLock(
            $this->clock,
            $context->option()->lockTimoutMs(),
            $context->option()->updateLockThreshold()
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
            $this->clock,
            $this->messageAlias,
            $eventCounter,
            $streamCache
        );
    }

    private function newProjectorRepository(ProjectorContext $context,
                                            string $streamName,
                                            ?ReadModel $readModel): ProjectorRepository
    {
        $repositoryClass = $readModel instanceof ReadModel
            ? ReadModelRepository::class : ProjectionRepository::class;

        $repository = new $repositoryClass(
            $context,
            $this->projectionProvider,
            $this->newTimeLock($context),
            $this->jsonEncoder,
            $streamName,
            $readModel ?? $this->chronicler
        );

        if ($this->eventDispatcher) {
            $repository = new EventsProjectorRepository($repository, $this->eventDispatcher);
        }

        return $repository;
    }
}
