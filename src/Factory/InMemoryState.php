<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Projecting\ProjectionState;

final class InMemoryState implements ProjectionState
{
    private array $state = [];

    public function setState(array $state): void
    {
        $this->state = $state;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function resetState(): void
    {
        $this->state = [];
    }
}
