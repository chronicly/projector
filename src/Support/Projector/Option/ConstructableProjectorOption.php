<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Projector\Option;

use Chronhub\Contracts\Projecting\ProjectorOption;

final class ConstructableProjectorOption implements ProjectorOption
{
    use HasArrayableProjectorOption;

    public function __construct(
        private bool $dispatchPcntlSignal = false,
        private int $streamCacheSize = 1000,
        private int $lockTimeoutMs = 1000,
        private int $sleep = 10000,
        private int $persistBlockSize = 1000,
        private int $updateLockThreshold = 0,
        private array $retriesMs = [0, 5, 100, 500, 1000, 2000, 3000],
        private string $detectionWindows = 'PT60S')
    {
    }

    public function dispatchSignal(): bool
    {
        return $this->dispatchPcntlSignal;
    }

    public function streamCacheSize(): int
    {
        return $this->streamCacheSize;
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

    public function retriesMs(): array
    {
        return $this->retriesMs;
    }

    public function detectionWindows(): string
    {
        return $this->detectionWindows;
    }
}
