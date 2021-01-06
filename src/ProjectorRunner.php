<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\PersistentProjector;
use Chronhub\Contracts\Projecting\Projector;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Contracts\Projecting\QueryProjector;
use Chronhub\Projector\Exception\InvalidArgumentException;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PersistOrSleepBeforeResetCounter;
use Chronhub\Projector\Pipe\PreparePersistentRunner;
use Chronhub\Projector\Pipe\PrepareQueryRunner;
use Chronhub\Projector\Pipe\UpdateProjectionStatusAndPositions;

final class ProjectorRunner
{
    public function __construct(private Projector $projector,
                                private Chronicler $chronicler,
                                private MessageAlias $messageAlias,
                                private ?ProjectorRepository $repository)
    {
    }

    public function run(ProjectorContext $context): void
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
     * @return array
     */
    private function getPipes(): array
    {
        if ($this->projector instanceof QueryProjector) {
            return [
                new PrepareQueryRunner(),
                new HandleStreamEvent($this->chronicler, $this->messageAlias, null),
                new DispatchSignal()
            ];
        }

        if ($this->projector instanceof PersistentProjector) {
            return [
                new PreparePersistentRunner($this->projector, $this->repository),
                new HandleStreamEvent($this->chronicler, $this->messageAlias, $this->repository),
                new PersistOrSleepBeforeResetCounter($this->repository),
                new DispatchSignal(),
                new UpdateProjectionStatusAndPositions($this->projector, $this->repository)
            ];
        }

        throw new InvalidArgumentException("Invalid projector");
    }
}
