<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Facade;

use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorManager;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryScope;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ProjectorManager create(string $driver = 'default')
 */
final class Project extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'projector.manager';
    }

    public static function createQuery(string $name = 'default',
                                       array $options = [],
                                       ?ProjectionQueryScope $queryScope = null): ProjectorFactory
    {
        $projector = self::create($name);

        $queryScope = $queryScope ?? $projector->queryScope();

        return $projector
            ->createQuery($options)
            ->withQueryFilter($queryScope->fromIncludedPosition());
    }

    public static function createProjection(string $streamName,
                                            string $name = 'default',
                                            array $options = [],
                                            ?ProjectionQueryScope $queryScope = null): ProjectorFactory
    {
        $projector = self::create($name);

        $queryScope = $queryScope ?? $projector->queryScope();

        return $projector
            ->createProjection($streamName, $options)
            ->withQueryFilter($queryScope->fromIncludedPosition());
    }

    public static function createReadModel(string $streamName,
                                           string|ReadModel $readModel,
                                           string $name = 'default',
                                           array $options = [],
                                           ?ProjectionQueryScope $queryScope = null): ProjectorFactory
    {
        $projector = self::create($name);

        $queryScope = $queryScope ?? $projector->queryScope();

        return $projector
            ->createReadModelProjection($streamName, $readModel, $options)
            ->withQueryFilter($queryScope->fromIncludedPosition());
    }
}
