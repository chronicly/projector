<?php
declare(strict_types=1);

namespace Chronhub\Projector\Console;

use Chronhub\Contracts\Aggregate\AggregateChanged;

final class ProjectCategoryStreamCommand extends AbstractPersistentProjectionCommand
{
    protected $signature = 'projector:category {--projector=default} {--signal=1}';

    public function handle(): void
    {
        $projection = $this->withProjection('$by_category');

        $projection
            ->fromAll()
            ->whenAny(function (AggregateChanged $event): void {
                $streamName = $this->streamName();
                $pos = strpos($streamName, '-');

                if (false === $pos) {
                    return;
                }

                $category = substr($streamName, 0, $pos);

                $this->linkTo('$ct-' . $category, $event);
            })->run(true);
    }
}
