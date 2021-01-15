<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;

final class PersistOrUpdateLockBeforeResetCounter implements Pipe
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $context->counter()->isReset()
            ? $this->sleepBeforeUpdateLock($context->option()->sleep())
            : $this->repository->persist();

        $context->counter()->reset();

        return $next($context);
    }

    private function sleepBeforeUpdateLock(int $sleep): void
    {
        usleep($sleep);

        $this->repository->updateLock();
    }
}
