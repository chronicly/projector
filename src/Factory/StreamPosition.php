<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Clock\PointInTime;
use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Projector\Exception\RuntimeException;
use function array_key_exists;
use function array_merge;
use function usleep;

final class StreamPosition implements Position
{
    /**
     * @var array<string,int>
     */
    private array $container = [];
    private int $retries = 0;
    private bool $gapDetected = false;

    public function __construct(private EventStreamProvider $eventStreamProvider,
                                private Clock $clock,
                                private array $retriesMs,
                                private string $detectionWindows = 'PT60S')
    {
    }

    public function watch(array $streamNames): void
    {
        $container = [];

        foreach ($this->loadStreams($streamNames) as $realStreamName) {
            $container[$realStreamName] = 0;
        }

        $this->container = array_merge($container, $this->container);
    }

    public function discover(array $streamsPositions): void
    {
        $this->container = array_merge($this->container, $streamsPositions);
    }

    public function bind(string $streamName, int $position): void
    {
        $this->container[$streamName] = $position;
    }

    public function reset(): void
    {
        $this->container = [];
    }

    public function gapDetected(): bool
    {
        return $this->gapDetected;
    }

    public function setGapDetected(bool $gapDetected): void
    {
        $this->gapDetected = $gapDetected;
    }

    public function sleepWithGapDetected(): void
    {
        usleep($this->retriesMs[$this->retries]);

        $this->retries++;
    }

    public function resetRetries(): void
    {
        $this->retries = 0;
    }

    public function hasGap(string $streamName, int $position, PointInTime $time): bool
    {
        if (count($this->retriesMs) < 1) {
            return false;
        }

        $now = $this->clock->pointInTime()->sub($this->detectionWindows);

        if ($now->after($time)) {
            return false;
        }

        if ($this->container[$streamName] + 1 === $position) {
            return false;
        }

        return array_key_exists($this->retries, $this->retriesMs);
    }

    public function all(): array
    {
        return $this->container;
    }

    /**
     * @param array $streamNames
     * @return string[]
     */
    private function loadStreams(array $streamNames): array
    {
        if (isset($streamNames['all'])) {
            return $this->eventStreamProvider->allStreamWithoutInternal();
        }

        if (isset($streamNames['categories'])) {
            return $this->eventStreamProvider->filterByCategories($streamNames['categories']);
        }

        $streamNames = $streamNames['names'] ?? [];

        if (count($streamNames) < 1) {
            throw new RuntimeException('Invalid configuration, stream names can not be empty');
        }

        return $streamNames;
    }
}
