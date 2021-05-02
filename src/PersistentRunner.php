<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleGap;
use Chronhub\Projector\Pipe\HandleCountdown;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PersistOrUpdateLock;
use Chronhub\Projector\Pipe\PreparePersistentRunner;
use Chronhub\Projector\Pipe\ResetEventCounter;
use Chronhub\Projector\Pipe\StopWhenRunningOnce;
use Chronhub\Projector\Pipe\UpdateStatusAndPositions;

final class PersistentRunner
{
    public function __construct(private PersistentProjector $projector,
                                private Chronicler $chronicler,
                                private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context): void
    {
        $pipeline = new Pipeline($this->repository);

        $pipeline->through($this->getPipes());

        do {
            $isStopped = $pipeline
                ->send($context)
                ->then(fn(ProjectorContext $context): bool => $context->runner()->isStopped());
        } while ($context->runner()->inBackground() && !$isStopped);
    }

    private function getPipes(): array
    {
        return [
            new HandleCountdown($this->projector),
            new PreparePersistentRunner($this->repository),
            new HandleStreamEvent($this->chronicler, $this->repository),
            new PersistOrUpdateLock($this->repository),
            new HandleGap($this->repository),
            new ResetEventCounter(),
            new DispatchSignal(),
            new UpdateStatusAndPositions($this->repository),
            new StopWhenRunningOnce($this->projector)
        ];
    }
}
