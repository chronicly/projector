<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Facade;

use Chronhub\Contracts\Projecting\ProjectorManager;
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
}
