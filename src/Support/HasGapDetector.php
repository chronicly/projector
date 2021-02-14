<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support;

use JetBrains\PhpStorm\Pure;
use function array_key_exists;
use function usleep;

trait HasGapDetector
{
    private ?string $interval;
    private int $retries = 0;
    private array $retriesMs = [0, 5, 50, 500,];

    protected function handleGapDetected(bool $gapDetected): void
    {
        if ($gapDetected) {
            usleep($this->retriesMs[$this->retries]);

            $this->retries++;

            $this->repository->persist();
        } else {
            $this->retries = 0;
        }
    }

    #[Pure]
    protected function hasGap(int $streamPosition, int $eventPosition): bool
    {
        return $streamPosition + 1 !== $eventPosition && array_key_exists($this->retries, $this->retriesMs);
    }
}
