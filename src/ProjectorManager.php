<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Model\ProjectionProvider;
use Chronhub\Contracts\Projecting\ProjectorFactory;
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
use Chronhub\Projector\Projecting\PersistentContext;
use Chronhub\Projector\Projecting\Projection\ProjectionRepository;
use Chronhub\Projector\Projecting\Projection\ProjectProjection;
use Chronhub\Projector\Projecting\ProjectionContext;
use Chronhub\Projector\Projecting\ProjectorRepository;
use Chronhub\Projector\Projecting\Query\ProjectQuery;
use Chronhub\Projector\Projecting\ReadModel\ProjectReadModel;
use Chronhub\Projector\Projecting\ReadModel\ReadModelRepository;
use Illuminate\Database\QueryException;

final class ProjectorManager implements \Chronhub\Contracts\Projecting\ProjectorManager
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
        $options = empty($options) ? $this->options : $options;
        $option = new Option(...$options);

        $context = new Context(
            $option,
            new StreamPosition($this->eventStreamProvider),
            new InMemoryState(),
            ProjectionStatus::IDLE(),
        );

        return new ProjectQuery($context, $this->chronicler, $this->messageAlias);
    }

    public function createProjection(string $streamName, array $options = []): ProjectorFactory
    {
        $options = empty($options) ? $this->options : $options;
        $option = new Option(...$options);

        $context = new ProjectionContext(
            $option,
            new StreamPosition($this->eventStreamProvider),
            new InMemoryState(),
            ProjectionStatus::IDLE(),
            new EventCounter(),
            new StreamCache($option->persistBlockSize())
        );

        $repository = new ProjectorRepository(
            $context, $this->projectionProvider, $this->jsonEncoder, $streamName
        );

        $decorator = new ProjectionRepository($repository, $this->chronicler);

        return new ProjectProjection(
            $context, $decorator, $this->chronicler, $this->messageAlias, $streamName
        );
    }

    public function createReadModelProjection(string $streamName, ReadModel $readModel, array $options = []): ProjectorFactory
    {
        $options = empty($options) ? $this->options : $options;
        $option = new Option(...$options);

        $context = new PersistentContext(
            $option,
            new StreamPosition($this->eventStreamProvider),
            new InMemoryState(),
            ProjectionStatus::IDLE(),
            new EventCounter()
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
}
