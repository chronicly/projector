<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

final class EventTimer
{
    private float $now = 0;

    /**
     * EventTimer constructor.
     *
     * @param float $runningTimeMs in milliseconds
     */
    public function __construct(private float $runningTimeMs = 1000)
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
        $this->runningTimeMs -= $this->now() - $this->now;
    }

    public function now(): float
    {
        return microtime(true);
    }

    public function isReached(): bool
    {
        return $this->runningTimeMs <= 0;
    }

    public function stopAt(): float
    {
        return $this->runningTimeMs;
    }
}
