<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\PersistentProjectorContext;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use JetBrains\PhpStorm\Pure;

class PersistentContext extends Context implements PersistentProjectorContext
{
    #[Pure]
    public function __construct(ProjectorOption $option,
                                Position $position,
                                ProjectionState $state,
                                Status $status,
                                private EventCounter $counter)
    {
        parent::__construct($option, $position, $state, $status);
    }

    public function counter(): EventCounter
    {
        return $this->counter;
    }
}
