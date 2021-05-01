<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Timer;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Clock\PointInTime;
use Chronhub\Contracts\Projecting\ProjectorTimer;

final class ProcessTimer implements ProjectorTimer
{
    private ?int $endAt;

    public function __construct(private Clock $clock, private int|string $timer)
    {
    }

    public function start(): void
    {
        if (null === $this->endAt) {
            $now = $this->clock->pointInTime();

            $this->endAt = $this->determineTimer($now);
        }
    }

    public function isExpired(): bool
    {
        return microtime(true) >= $this->endAt;
    }

    private function determineTimer(PointInTime $pointInTime): int
    {
        if (is_integer($this->timer)) {
            return $pointInTime->dateTime()->getTimestamp() + $this->timer;
        }

        return $pointInTime->add($this->timer)->dateTime()->getTimestamp();
    }
}
