<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\Projector;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Projecting\Factory\Pipeline;
use Chronhub\Projector\Projecting\Pipe\DispatchSignal;
use Chronhub\Projector\Projecting\Pipe\HandleStreamEvent;
use Chronhub\Projector\Projecting\Pipe\PersistOrSleepBeforeResetCounter;
use Chronhub\Projector\Projecting\Pipe\PreparePersistentRunner;
use Chronhub\Projector\Projecting\Pipe\PrepareQueryRunner;
use Chronhub\Projector\Projecting\Pipe\UpdateProjectionStatusAndPositions;

final class ProjectorRunner
{
    public function __construct(private Projector $projector,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias,
                                private ?ProjectorRepository $repository)
    {
    }

    public function process(ProjectorContext $context): void
    {
        try {
            $pipeline = new Pipeline();
            $pipeline->through($this->getPipes());

            do {
                $isStopped = $pipeline
                    ->send($context)
                    ->then(fn(ProjectorContext $context): bool => $context->isStopped());
            } while ($context->keepRunning() && !$isStopped);
        } finally {
            if ($this->repository) {
                $this->repository->releaseLock();
            }
        }
    }

    /**
     * @return array<Pipe>
     */
    private function getPipes(): array
    {
        if (!$this->projector instanceof PersistentProjector) {
            return [
                new PrepareQueryRunner(),
                new HandleStreamEvent($this->chronicler, $this->messageAlias, null),
                new DispatchSignal()
            ];
        }

        return [
            new PreparePersistentRunner($this->projector, $this->repository),
            new HandleStreamEvent($this->chronicler, $this->messageAlias, $this->repository),
            new PersistOrSleepBeforeResetCounter($this->repository),
            new DispatchSignal(),
            new UpdateProjectionStatusAndPositions($this->projector, $this->repository)
        ];
    }
}
