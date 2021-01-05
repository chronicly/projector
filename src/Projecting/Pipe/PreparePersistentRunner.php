<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Projecting\Concern\HasRemoteProjectionStatus;

final class PreparePersistentRunner implements Pipe
{
    use HasRemoteProjectionStatus;

    private bool $hasBeenPrepared = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$context instanceof PersistentProjectorContext) {
            throw new RuntimeException("Invalid projector context");
        }

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
