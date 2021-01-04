<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Factory;

use Chronhub\Contracts\Projecting\ProjectorOption;

final class Option implements ProjectorOption
{
    public function __construct(
        private bool $dispatchPcntlSignal = false,
        private int $lockTimeoutMs = 1000,
        private int $sleep = 10000,
        private int $persistBlockSize = 1000,
        private int $updateLockThreshold = 0
    )
    {
    }

    public function dispatchSignal(): bool
    {
        return $this->dispatchPcntlSignal;
    }

    public function lockTimoutMs(): int
    {
        return $this->lockTimeoutMs;
    }

    public function sleep(): int
    {
        return $this->sleep;
    }

    public function persistBlockSize(): int
    {
        return $this->persistBlockSize;
    }

    public function updateLockThreshold(): int
    {
        return $this->updateLockThreshold;
    }
}
