<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\QueryProjector;

final class ContextualQuery implements ContextualEventHandler
{
    public function __construct(private QueryProjector $query, private ProjectorContext $context)
    {
    }

    public function stop(): void
    {
        $this->query->stop();
    }

    public function streamName(): ?string
    {
        return $this->context->currentStreamName();
    }
}
