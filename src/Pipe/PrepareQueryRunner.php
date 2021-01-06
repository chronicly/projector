<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;

final class PrepareQueryRunner implements Pipe
{
    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            $context->position()->make($context->streamsNames());
        }

        return $next($context);
    }
}
