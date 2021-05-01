<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\ProjectorTimer;
use Chronhub\Projector\Context\ProjectorContext;

final class HandleProcessTimer
{
    private ?ProjectorTimer $timer = null;

    public function __construct(private ?PersistentProjector $projector)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (null === $this->timer) {
            $this->timer = $context->timer();

            $this->timer->start();
        }

        $result = $next($context);

        if (!$context->runner()->isStopped() && $this->timer->isExpired()) {
            $this->projector
                ? $this->projector->stop()
                : $context->runner()->stop(true);
        }

        return $result;
    }
}
