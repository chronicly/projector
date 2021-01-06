<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Projecting\EventCounter as Counter;

final class EventCounter implements Counter
{
    private int $counter = 0;

    public function increment(): void
    {
        $this->counter++;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }

    public function isReset(): bool
    {
        return 0 === $this->counter;
    }

    public function equals(int $num): bool
    {
        return $this->counter === $num;
    }

    public function current(): int
    {
        return $this->counter;
    }
}
