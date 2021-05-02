<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Projector\Concern\HasRemoteProjectionStatus;
use Chronhub\Projector\Context\ProjectorContext;

final class UpdateStatusAndPositions
{
    use HasRemoteProjectionStatus;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $this->loadRemoteStatus($context->runner()->inBackground());

        $context->position->watch($context->streamsNames());

        return $next($context);
    }
}
