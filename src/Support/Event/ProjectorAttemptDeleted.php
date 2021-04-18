<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Event;

final class ProjectorAttemptDeleted
{
    public function __construct(private string $name,
                                private bool $withEvents)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function withEvents(): bool
    {
        return $this->withEvents;
    }
}
