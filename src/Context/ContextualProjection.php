<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Projecting\PersistentProjectionProjector;
use Chronhub\Contracts\Projecting\ProjectionEventHandler as ContextualEventHandler;

final class ContextualProjection implements ContextualEventHandler
{
    private PersistentProjectionProjector $projector;
    private ?string $streamName;

    public function __construct(PersistentProjectionProjector $projector, ?string &$streamName)
    {
        $this->projector = $projector;
        $this->streamName = &$streamName;
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
        return $this->streamName;
    }
}