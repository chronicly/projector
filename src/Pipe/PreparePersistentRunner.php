<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Projector\Concern\HasRemoteProjectionStatus;
use Chronhub\Projector\Context\ProjectorContext;

final class PreparePersistentRunner
{
    use HasRemoteProjectionStatus;

    private bool $isInitiated = false;

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$this->isInitiated) {
            $this->isInitiated = true;

            if ($this->stopOnLoadingRemoteStatus($context->runner()->inBackground())) {
                return true;
            }

            $this->repository->initiate();
        }

        return $next($context);
    }
}
