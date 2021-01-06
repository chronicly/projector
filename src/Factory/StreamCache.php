<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Projecting\StreamCache as Cache;
use Chronhub\Projector\Exception\InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

final class StreamCache implements Cache
{
    private array $container;
    private int $position = -1;

    public function __construct(private int $size)
    {
        if ($size <= 0) {
            throw new InvalidArgumentException('Size must be greater than 0');
        }

        $this->container = array_fill(0, $size, null);
    }

    public function push(string $streamName): void
    {
        $this->container[$this->nextPosition()] = $streamName;
    }

    #[Pure]
    public function has(string $streamName): bool
    {
        return in_array($streamName, $this->container, true);
    }

    public function all(): array
    {
        return $this->container;
    }

    private function nextPosition(): int
    {
        return $this->position = ++$this->position % $this->size;
    }
}
