<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorManager as Manager;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Chronhub\Projector\Concern\HasConstructableProjectorManager;
use Chronhub\Projector\Concern\HasReadProjectorManager;
use Chronhub\Projector\Concern\HasWriteProjectorManager;
use Chronhub\Projector\Exception\ProjectionNotFound;
use Chronhub\Projector\Factory\EventCounter;
use Chronhub\Projector\Factory\StreamCache;

final class ProjectorManager implements Manager
{
    use HasConstructableProjectorManager, HasReadProjectorManager, HasWriteProjectorManager;

    public function createQuery(array $options = []): ProjectorFactory
    {
        $context = $this->newProjectorContext(
            $this->newProjectorOption($options),
            null,
            null
        );

        return new ProjectQuery($context, $this->chronicler);
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
            $context, $repository, $this->chronicler, $streamName
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
            $context, $repository, $this->chronicler, $streamName, $readModel
        );
    }

    public function queryScope(): ProjectionQueryScope
    {
        return $this->projectionQueryScope;
    }

    protected function assertProjectionNameExists(string $projectionName): void
    {
        if (!$this->exists($projectionName)) {
            throw ProjectionNotFound::withName($projectionName);
        }
    }
}
