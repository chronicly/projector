<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;

final class DispatchSignal implements Pipe
{
    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $context->dispatchSignal();

        return $next($context);
    }
}
