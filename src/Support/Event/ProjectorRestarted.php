<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Event;

final class ProjectorRestarted
{
    public function __construct(private string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }
}
