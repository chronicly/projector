<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

final class EventTimer
{
    private float $now = 0;

    /**
     * EventTimer constructor.
     *
     * @param float $stopAt in milliseconds
     */
    public function __construct(private float $stopAt = 1000)
    {
    }

    public function start(): void
    {
        if ($this->now < 1) {
            $this->now = $this->now();
        }
    }

    public function increment(): void
    {
        $this->stopAt -= ($this->now() - $this->now);
    }

    public function isReached(): bool
    {
        return $this->stopAt <= 0;
    }

    public function now(): float
    {
        return microtime(true);
    }
}
