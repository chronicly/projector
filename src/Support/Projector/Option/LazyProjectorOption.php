<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Projector\Option;

use Chronhub\Contracts\Projecting\ProjectorOption;

final class LazyProjectorOption implements ProjectorOption
{
    use HasArrayableProjectorOption;

    public function dispatchSignal(): bool
    {
        return true;
    }

    public function streamCacheSize(): int
    {
        return 1000;
    }

    public function lockTimoutMs(): int
    {
        return 5000;
    }

    public function sleep(): int
    {
        return 100000;
    }

    public function persistBlockSize(): int
    {
        return 1000;
    }

    public function updateLockThreshold(): int
    {
        return 5000;
    }

    public function retriesMs(): array
    {
        return range(1, 5000, 5);
    }

    public function detectionWindows(): string
    {
        return 'PT60S';
    }
}
