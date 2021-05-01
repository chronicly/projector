<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Timer;

use Chronhub\Contracts\Projecting\ProjectorTimer;

final class NullTimer implements ProjectorTimer
{
    public function start(): void
    {
        //
    }

    public function isExpired(): bool
    {
        return false;
    }
}
