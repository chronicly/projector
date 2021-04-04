<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

final class EventTimer
{
    private float $now = 0;
    private float $elapsedTime = 0;

    /**
     * EventTimer constructor.
     *
     * @param int $stopAt in milliseconds
     */
    public function __construct(private int $stopAt = 1000)
    {
    }

    public function start(): void
    {
        if ($this->now === 0) {
            $this->now = $this->now();
        }
    }

    public function increment(): void
    {
        $this->elapsedTime += $this->now() - microtime(true);
    }

    public function elapsedTime(): float
    {
        return $this->elapsedTime;
    }

    public function isReached(): bool
    {
        return $this->elapsedTime >= $this->stopAt;
    }

    public function now(): float
    {
        return microtime(true);
    }
}
