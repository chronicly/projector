<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\QueryProjector;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PrepareQueryRunner;

final class QueryRunner
{
    public function __construct(private QueryProjector $projector,
                                private Chronicler $chronicler)
    {
    }

    public function __invoke(ProjectorContext $context): void
    {
        $pipeline = new Pipeline();

        $pipeline->through($this->getPipes());

        do {
            $isStopped = $pipeline
                ->send($context)
                ->then(fn(ProjectorContext $context): bool => $context->runner()->isStopped());
        } while ($context->runner()->inBackground() && !$isStopped);
    }

    /**
     * @return Pipe[]
     */
    private function getPipes(): array
    {
        return [
            new PrepareQueryRunner(),
            new HandleStreamEvent($this->chronicler, null),
            new DispatchSignal()
        ];
    }
}
