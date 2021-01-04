<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Query;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\QueryProjector;

final class QueryEventHandler implements ContextualEventHandler
{
    public function __construct(private QueryProjector $query, private ?string &$streamName)
    {
    }

    public function stop(): void
    {
        $this->query->stop();
    }

    public function streamName(): ?string
    {
        return $this->streamName;
    }
}
