<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;

final class PersistOrUpdateLock
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$context->position->hasGap()) {
            $context->eventCounter->isReset()
                ? $this->sleepBeforeUpdateLock($context->option->sleep())
                : $this->repository->persist();
        }

        return $next($context);
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep($sleep);

        $this->repository->updateLock();
    }
}
