<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Option;

use Chronhub\Contracts\Projecting\ProjectorOption;

final class InMemoryProjectorOption implements ProjectorOption
{
    use HasArrayableProjectorOption;

    public function dispatchSignal(): bool
    {
        return false;
    }

    public function streamCacheSize(): int
    {
        return 1000;
    }

    public function lockTimoutMs(): int
    {
        return 0;
    }

    public function sleep(): int
    {
        return 0;
    }

    public function persistBlockSize(): int
    {
        return 1;
    }

    public function updateLockThreshold(): int
    {
        return 0;
    }

    public function retriesMs(): array
    {
        return [];
    }

    public function detectionWindows(): string
    {
        return 'PT5S';
    }
}
