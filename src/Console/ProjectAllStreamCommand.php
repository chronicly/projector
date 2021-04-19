<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;
use Closure;

final class ProjectAllStreamCommand extends AbstractPersistentProjectionCommand
{
    protected $signature = 'projector:all_stream {--projector=default} {--signal=1}';

    public function handle(): void
    {
        $this->withProjection('$all');

        $this->projector
            ->fromAll()
            ->whenAny($this->eventHandler())
            ->run(true);
    }

    private function eventHandler(): Closure
    {
        return function (AggregateChanged $event): void {
            $this->emit($event);
        };
    }
}
