<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Projector\Context\ProjectorContext;

final class ResetEventCounter
{
    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $context->eventCounter->reset();

        return $next($context);
    }
}
