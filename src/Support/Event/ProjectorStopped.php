<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Event;

final class ProjectorStopped
{
    public function __construct(private string $name, private array $state)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function state(): array
    {
        return $this->state;
    }
}
