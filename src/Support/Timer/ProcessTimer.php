<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Timer;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Clock\PointInTime;
use Chronhub\Contracts\Projecting\ProjectorTimer;
use Chronhub\Projector\Exception\RuntimeException;

final class ProcessTimer implements ProjectorTimer
{
    private ?int $endAt = null;

    public function __construct(private Clock $clock, private int|string $timer)
    {
        if (is_integer($timer) && $timer < 1) {
            throw new RuntimeException("Integer projector timer must be greater than zero, current is $timer");
        }
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
        return $this->clock->dateTime()->getTimestamp() >= $this->endAt;
    }

    private function determineTimer(PointInTime $pointInTime): int
    {
        if (is_integer($this->timer)) {
            return $pointInTime->dateTime()->getTimestamp() + $this->timer;
        }

        return $pointInTime->add($this->timer)->dateTime()->getTimestamp();
    }
}
