<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Projecting\Concern\HasRemoteProjectionStatus;

final class UpdateProjectionStatusAndPositions implements Pipe
{
    use HasRemoteProjectionStatus;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if ($context instanceof PersistentProjectorContext) {
            $this->processOnStatus(false, $context->keepRunning());

            $context->position()->make($context->streamsNames());
        }

        return $next($context);
    }
}
