<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Pipe;

use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Exception\RuntimeException;

final class PersistOrSleepBeforeResetCounter implements Pipe
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        if (!$context instanceof PersistentProjectorContext) {
            throw new RuntimeException("Invalid projector context");
        }

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
