<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Projecting\PersistentProjectionProjector;
use Chronhub\Contracts\Projecting\ProjectionEventHandler as ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorContext;

final class ContextualProjection implements ContextualEventHandler
{
    public function __construct(private PersistentProjectionProjector $projector,
                                private ProjectorContext $context)
    {
    }

    public function stop(): void
    {
        $this->projector->stop();
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $this->projector->linkTo($streamName, $event);
    }

    public function emit(DomainEvent $event): void
    {
        $this->projector->emit($event);
    }

    public function streamName(): ?string
    {
        return $this->context->currentStreamName;
    }
}
