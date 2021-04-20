<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Concern\HasRemoteProjectionStatus;

final class UpdateProjectionStatusAndPositions implements Pipe
{
    use HasRemoteProjectionStatus;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $this->stopOnLoadingRemoteStatus(false, $context->runner()->inBackground());

        $context->position()->make($context->streamsNames());

        return $next($context);
    }
}
