<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Model\EventStreamProvider;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Projector\Exception\RuntimeException;

final class StreamPosition implements Position
{
    /**
     * @var array<string,int>
     */
    private array $container = [];

    public function __construct(private EventStreamProvider $eventStreamProvider)
    {
    }

    public function make(array $streamNames): void
    {
        $container = [];

        foreach ($this->gatherStreamNames($streamNames) as $realStreamName) {
            $container[$realStreamName] = 0;
        }

        $this->container = array_merge($container, $this->container);
    }

    public function merge(array $streamsPositions): void
    {
        $this->container = array_merge($this->container, $streamsPositions);
    }

    public function setAt(string $streamName, int $position): void
    {
        $this->container[$streamName] = $position;
    }

    public function reset(): void
    {
        $this->container = [];
    }

    public function all(): array
    {
        return $this->container;
    }

    /**
     * @param array $streamNames
     * @return string[]
     */
    private function gatherStreamNames(array $streamNames): array
    {
        if (isset($streamNames['all'])) {
            return $this->eventStreamProvider->allStreamWithoutInternal();
        }

        if (isset($streamNames['categories'])) {
            return $this->eventStreamProvider->filterByCategories($streamNames['categories']);
        }

        $streamNames = $streamNames['names'] ?? [];

        if (empty($streamNames)) {
            throw new RuntimeException('Invalid configuration, stream names can not be empty');
        }

        $this->assertStreamNamesExists($streamNames);

        return $streamNames;
    }

    private function assertStreamNamesExists(array $streamNames): void
    {
        $remoteStreams = $this->eventStreamProvider->filterByStreams($streamNames);

        if (count($streamNames) !== count($remoteStreams)) {
            $message = "One or many stream names were not found in event stream table,\n";
            $message .= "Missing " . implode(', ', array_diff($streamNames, $remoteStreams));

            throw new RuntimeException($message);
        }
    }
}
