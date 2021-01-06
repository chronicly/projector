<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Concern\HasRemoteProjectionStatus;

final class PreparePersistentRunner implements Pipe
{
    use HasRemoteProjectionStatus;

    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$this->hasBeenPrepared) {
            $this->hasBeenPrepared = true;

            if ($this->processOnStatus(true, $context->keepRunning())) {
                return true;
            }

            $this->repository->prepare(null);
        }

        return $next($context);
    }
}
