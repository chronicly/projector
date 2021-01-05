<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Projecting\Concern\HasRemoteProjectionStatus;

final class UpdateProjectionStatusAndPositions implements Pipe
{
    use HasRemoteProjectionStatus;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$context instanceof PersistentProjectorContext) {
            throw new RuntimeException("Invalid projector context");
        }

        $this->processOnStatus(false, $context->keepRunning());

        $context->position()->make($context->streamsNames());

        return $next($context);
    }
}
