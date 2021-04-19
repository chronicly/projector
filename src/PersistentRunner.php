<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PersistOrUpdateLock;
use Chronhub\Projector\Pipe\PreparePersistentRunner;
use Chronhub\Projector\Pipe\UpdateProjectionStatusAndPositions;

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
                ->then(function (ProjectorContext $context): bool {
                    // to be consistent when dispatching projector events
                    // we stop explicitly the projection when it's not running
                    // in background
                    if (!$context->runner()->inBackground()) {
                        $this->projector->stop();
                    }

                    return $context->runner()->isStopped();
                });
        } while ($context->runner()->inBackground() && !$isStopped);
    }

    /**
     * @return Pipe[]
     */
    private function getPipes(): array
    {
        return [
            new PreparePersistentRunner($this->repository),
            new HandleStreamEvent($this->chronicler, $this->repository),
            new PersistOrUpdateLock($this->repository),
            new DispatchSignal(),
            new UpdateProjectionStatusAndPositions($this->repository)
        ];
    }
}
