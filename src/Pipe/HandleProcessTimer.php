<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Projector\Context\ProjectorContext;

final class HandleProcessTimer
{
    public function __construct(private ?PersistentProjector $projector)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $context->timer()->start();

        $result = $next($context);

        if (!$context->runner()->isStopped() && $context->timer()->isExpired()) {
            $this->projector
                ? $this->projector->stop()
                : $context->runner()->stop(true);
        }

        return $result;
    }
}
