<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectionProjectorContext;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamCache;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use JetBrains\PhpStorm\Pure;

final class ProjectionContext extends PersistentContext implements ProjectionProjectorContext
{
    private bool $isStreamCreated = false;

    #[Pure]
    public function __construct(ProjectorOption $option,
                                Position $position,
                                ProjectionState $state,
                                Status $status,
                                EventCounter $counter,
                                private StreamCache $cache)
    {
        parent::__construct($option, $position, $state, $status, $counter);
    }

    public function isStreamCreated(): bool
    {
        return $this->isStreamCreated;
    }

    public function setStreamCreated(): void
    {
        $this->isStreamCreated = true;
    }

    public function cache(): StreamCache
    {
        return $this->cache;
    }
}
