<?php
declare(strict_types=1);

namespace Chronhub\Projector\Exception;

use Chronhub\Chronicler\Stream\StreamName;
use JetBrains\PhpStorm\Pure;

class ProjectionNotFound extends RuntimeException
{
    #[Pure]
    public static function withStreamName(StreamName $streamName): self
    {
        return new self("Projection with stream name {$streamName} not found");
    }

    #[Pure]
    public static function withName(string $projectionName): self
    {
        return new self("Projection name {$projectionName} not found");
    }
}
