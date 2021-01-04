<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Projecting\Factory\ContextBuilder;
use Closure;
use JetBrains\PhpStorm\Pure;

class Context implements ProjectorContext
{
    private ?string $currentStreamName = null;
    private bool $isStopped = false;
    private ?ContextBuilder $builder;
    private bool $keepRunning = false;

    public function __construct(protected ProjectorOption $option,
                                protected Position $position,
                                protected ProjectionState $state,
                                protected Status $status)
    {
    }

    /**
     * @param ContextBuilder         $builder
     * @param ContextualEventHandler $eventHandler
     * @internal
     */
    public function setUp(ContextBuilder $builder, ContextualEventHandler $eventHandler): void
    {
        $initState = $builder->bindInitCallback($eventHandler);

        $this->state->setState($initState);

        $builder->bindEventHandlers($eventHandler);

        $this->builder = $builder;
    }

    #[Pure]
    public function hasSingleHandler(): bool
    {
        return $this->builder->getEventHandlers() instanceof Closure;
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

    public function keepRunning(): bool
    {
        return $this->keepRunning;
    }

    public function withKeepRunning(bool $keepRunning): void
    {
        $this->keepRunning = $keepRunning;
    }

    public function dispatchSignal(): void
    {
        if ($this->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
    }

    #[Pure]
    public function initCallback(): Closure|array
    {
        return $this->builder->getInitCallback();
    }

    #[Pure]
    public function eventHandlers(): Closure|array
    {
        return $this->builder->getEventHandlers();
    }

    #[Pure]
    public function streamsNames(): array
    {
        return $this->builder->getStreamsNames();
    }

    #[Pure]
    public function categories(): array
    {
        return $this->builder->getCategories();
    }

    #[Pure]
    public function queryFilter(): ProjectionQueryFilter
    {
        return $this->builder->getQueryFilter();
    }
}
