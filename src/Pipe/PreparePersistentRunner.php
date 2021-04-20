<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Projector\Concern\HasRemoteProjectionStatus;

final class PreparePersistentRunner implements Pipe
{
    use HasRemoteProjectionStatus;

    private bool $isInitiated = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$this->isInitiated) {
            $this->isInitiated = true;

            if ($this->stopOnLoadingRemoteStatus(true, $context->runner()->inBackground())) {
                return true;
            }

            $this->repository->initiate();
        }

        return $next($context);
    }
}
