<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamCache;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Projector\Projecting\Concern\HasContextFactory;
use Closure;
use JetBrains\PhpStorm\Pure;

class Context implements ProjectorContext
{
    use HasContextFactory;

    private ?string $currentStreamName = null;
    private bool $isStopped = false;
    private bool $keepRunning = false;
    private bool $isStreamCreated = false;

    public function __construct(protected ProjectorOption $option,
                                protected Position $position,
                                protected ProjectionState $state,
                                protected Status $status,
                                protected ?EventCounter $eventCounter,
                                protected ?StreamCache $streamCache)
    {
    }

    /**
     * @param ContextualEventHandler $eventHandler
     * @internal
     */
    public function setUp(ContextualEventHandler $eventHandler): void
    {
        $this->validate();

        $initState = $this->bindInitCallback($eventHandler);

        $this->state->setState($initState);

        $this->bindEventHandlers($eventHandler);
    }

    #[Pure]
    public function hasSingleHandler(): bool
    {
        return $this->eventHandlers() instanceof Closure;
    }

    public function isStopped(): bool
    {
        return $this->isStopped;
    }

    public function setCurrentStreamName(string $streamName): void
    {
        $this->currentStreamName = $streamName;
    }

    public function stopProjection(bool $stopProjection): void
    {
        $this->isStopped = $stopProjection;
    }

    public function currentStreamName(): ?string
    {
        return $this->currentStreamName;
    }

    public function state(): ProjectionState
    {
        return $this->state;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function status(): Status
    {
        return $this->status;
    }

    public function position(): StreamPosition
    {
        return $this->position;
    }

    public function option(): ProjectorOption
    {
        return $this->option;
    }

    public function dispatchSignal(): void
    {
        if ($this->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
    }

    public function counter(): ?EventCounter
    {
        return $this->eventCounter;
    }

    public function isStreamCreated(): bool
    {
       return $this->isStreamCreated;
    }

    public function setStreamCreated(): void
    {
        $this->isStreamCreated = true;
    }

    public function cache(): ?StreamCache
    {
        return $this->streamCache;
    }
}
