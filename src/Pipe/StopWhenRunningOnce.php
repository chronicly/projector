<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Projector\Context\ProjectorContext;

final class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        // to be consistent when dispatching projector events
        // we stop explicitly the projection when it's running once
        if (!$context->runner()->inBackground() && !$context->runner()->isStopped()) {
            $this->projector->stop();
        }

        return $next($context);
    }
}
