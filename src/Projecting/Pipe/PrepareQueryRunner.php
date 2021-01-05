<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Exception\RuntimeException;

final class PrepareQueryRunner
{
    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if ($context instanceof PersistentProjectorContext) {
            throw new RuntimeException("Invalid projector context");
        }

        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            $context->position()->make($context->streamsNames());
        }

        return $next($context);
    }
}
