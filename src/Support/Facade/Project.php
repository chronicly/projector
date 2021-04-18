<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Facade;

use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorManager;
use Chronhub\Contracts\Projecting\ReadModel;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
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
                                       ?ProjectionQueryFilter $queryFilter = null): ProjectorFactory
    {
        $projector = self::create($name);

        return $projector
            ->createQuery($options)
            ->withQueryFilter(
                $queryFilter ?? $projector->queryScope()->fromIncludedPosition()
            );
    }

    public static function createProjection(string $streamName,
                                            string $name = 'default',
                                            array $options = [],
                                            ?ProjectionQueryFilter $queryFilter = null): ProjectorFactory
    {
        $projector = self::create($name);

        return $projector
            ->createProjection($streamName, $options)
            ->withQueryFilter(
                $queryFilter ?? $projector->queryScope()->fromIncludedPosition()
            );
    }

    public static function createReadModel(string $streamName,
                                           string|ReadModel $readModel,
                                           string $name = 'default',
                                           array $options = [],
                                           ?ProjectionQueryFilter $queryFilter = null): ProjectorFactory
    {
        $projector = self::create($name);

        if (is_string($readModel)) {
            $readModel = self::$app->make($readModel);
        }

        return $projector
            ->createReadModelProjection($streamName, $readModel, $options)
            ->withQueryFilter(
                $queryFilter ?? $projector->queryScope()->fromIncludedPosition()
            );
    }
}
