<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;

final class ProjectAllStreamCommand extends AbstractPersistentProjectionCommand
{
    protected $signature = 'projector:all_stream {--projector=default} {--signal=1}';

    public function handle(): void
    {
        $projection = $this->withProjection('$all');

        $projection
            ->fromAll()
            ->whenAny(function (AggregateChanged $event): void {
                $this->emit($event);
            })->run(true);
    }
}
