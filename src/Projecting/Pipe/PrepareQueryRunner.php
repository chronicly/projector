<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorContext;

final class PrepareQueryRunner
{
    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$context instanceof PersistentProjectorContext && !$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            $context->position()->make($context->streamsNames());
        }

        return $next($context);
    }
}
