<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Factory\Pipeline;
use Chronhub\Projector\Pipe\DispatchSignal;
use Chronhub\Projector\Pipe\HandleCountdown;
use Chronhub\Projector\Pipe\HandleStreamEvent;
use Chronhub\Projector\Pipe\PrepareQueryRunner;

final class QueryRunner
{
    public function __construct(private Chronicler $chronicler)
    {
    }

    public function __invoke(ProjectorContext $context): void
    {
        $pipeline = new Pipeline(null);

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
            new HandleCountdown(null),
            new PrepareQueryRunner(),
            new HandleStreamEvent($this->chronicler, null),
            new DispatchSignal()
        ];
    }
}
