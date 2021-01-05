<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
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
use Chronhub\Projector\Projecting\Concern\HasReadProjectorManager;
use Chronhub\Projector\Projecting\Context;
use Chronhub\Projector\Projecting\Factory\EventCounter;
use Chronhub\Projector\Projecting\Factory\InMemoryState;
use Chronhub\Projector\Projecting\Factory\Option;
use Chronhub\Projector\Projecting\Factory\ProjectionStatus;
use Chronhub\Projector\Projecting\Factory\StreamCache;
use Chronhub\Projector\Projecting\Factory\StreamPosition;
use Chronhub\Projector\Projecting\ProjectionRepository;
use Chronhub\Projector\Projecting\ProjectorRepository;
use Chronhub\Projector\Projecting\ProjectProjection;
use Chronhub\Projector\Projecting\ProjectQuery;
use Chronhub\Projector\Projecting\ProjectReadModel;
use Chronhub\Projector\Projecting\ReadModelRepository;
use Illuminate\Database\QueryException;
use JetBrains\PhpStorm\Pure;

final class ProjectorManager implements Manager
{
    use HasReadProjectorManager;

    public function __construct(private Chronicler $chronicler,
                                private EventStreamProvider $eventStreamProvider,
                                protected ProjectionProvider $projectionProvider,
                                private MessageAlias $messageAlias,
                                private ProjectionQueryScope $projectionQueryScope,
                                private JsonEncoder $jsonEncoder,
                                private array $options = [])
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
            new StreamCache($options->persistBlockSize())
        );

        $decorator = new ProjectionRepository(
            $this->newProjectorRepository($streamName, $context),
            $this->chronicler
        );

        return new ProjectProjection(
            $context, $decorator, $this->chronicler, $this->messageAlias, $streamName
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

        $repository = new ProjectorRepository(
            $context, $this->projectionProvider, $this->jsonEncoder, $streamName
        );

        $decorator = new ReadModelRepository($repository, $readModel);

        return new ProjectReadModel(
            $context, $decorator, $this->chronicler, $this->messageAlias, $streamName, $readModel
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
                ['status' => $projectionStatus->getValue()]
            );
        } catch (QueryException $exception) {
            throw QueryFailure::fromQueryException($exception);
        }

        if (!$result) {
            $this->assertProjectionNameExists($streamName);
        }
    }

    #[Pure]
    private function newProjectorRepository(string $streamName, ProjectorContext $context): Repository
    {
        return new ProjectorRepository(
            $context, $this->projectionProvider, $this->jsonEncoder, $streamName
        );
    }

    #[Pure]
    private function newProjectorOption(array $options): ProjectorOption
    {
        $options = array_merge($this->options, $options);

        return new Option(...$options);
    }

    private function newProjectorContext(ProjectorOption $option,
                                         ?EventCounter $eventCounter,
                                         ?StreamCache $streamCache): ProjectorContext
    {
        return new Context(
            $option,
            new StreamPosition($this->eventStreamProvider),
            new InMemoryState(),
            ProjectionStatus::IDLE(),
            $eventCounter,
            $streamCache
        );
    }
}
