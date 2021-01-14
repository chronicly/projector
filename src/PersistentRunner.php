<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PersistOrSleepBeforeResetCounter;
use Chronhub\Projector\Pipe\PreparePersistentRunner;
use Chronhub\Projector\Pipe\UpdateProjectionStatusAndPositions;

final class PersistentRunner
{
    public function __construct(private PersistentProjector $projector,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias,
                                private ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context): void
    {
        try {
            $pipeline = new Pipeline();
            $pipeline->through($this->getPipes());

            do {
                $isStopped = $pipeline
                    ->send($context)
                    ->then(fn(ProjectorContext $context): bool => $context->runner()->isStopped());
            } while ($context->runner()->inBackground() && !$isStopped);
        } finally {
            $this->repository->releaseLock();
        }
    }

    /**
     * @return Pipe[]
     */
    private function getPipes(): array
    {
        return [
            new PreparePersistentRunner($this->projector, $this->repository),
            new HandleStreamEvent($this->chronicler, $this->messageAlias, $this->repository),
            new PersistOrSleepBeforeResetCounter($this->repository),
            new DispatchSignal(),
            new UpdateProjectionStatusAndPositions($this->projector, $this->repository)
        ];
    }
}
