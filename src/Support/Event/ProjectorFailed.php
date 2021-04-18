<?php
declare(strict_types=1);

namespace Chronhub\Projector\Support\Event;

use Throwable;

final class ProjectorFailed
{
    public function __construct(private string $streamName, private Throwable $exception)
    {
    }

    public function streamName(): string
    {
        return $this->streamName;
    }

    public function exception(): Throwable
    {
        return $this->exception;
    }
}
