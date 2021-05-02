<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;

final class HandleGap
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $context->position->gapDetected()
            ? $this->persistProjection($context)
            : $context->position->resetRetries();

        return $next($context);
    }

    private function persistProjection(ProjectorContext $context): void
    {
        $context->position->sleepWithGapDetected();

        $this->repository->persist();

        $context->position->resetGapDetected();
    }
}
