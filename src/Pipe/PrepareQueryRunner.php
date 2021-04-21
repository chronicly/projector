<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Projector\Context\ProjectorContext;

final class PrepareQueryRunner
{
    private bool $isInitiated = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$this->isInitiated) {
            $this->isInitiated = true;

            $context->position->watch($context->streamsNames());
        }

        return $next($context);
    }
}
