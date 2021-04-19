<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectionStatus as Status;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamPosition;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Projector\Concern\HasContextFactory;
use Chronhub\Projector\Factory\InMemoryState;
use Chronhub\Projector\Factory\ProjectionStatus;
use Closure;

class Context implements ProjectorContext
{
    use HasContextFactory;

    private ?string $currentStreamName = null;
    private bool $isStreamCreated = false;
    private ProjectionState $state;
    private ProjectionStatus $status;

    public function __construct(protected ProjectorOption $option,
                                protected Position $position,
                                protected Clock $clock,
                                protected ?EventCounter $eventCounter)
    {
        $this->state = new InMemoryState();
        $this->status = ProjectionStatus::IDLE();
    }

    public function cast(ContextualEventHandler $eventHandler): void
    {
        $this->validate();

        $initState = $this->castInitCallback($eventHandler);

        $this->state->setState($initState);

        $this->castEventHandlers($eventHandler);
    }

    public function resetStateWithInitialize(): ?array
    {
        $this->state->resetState();

        $callback = $this->initCallback;

        $state = null;

        if ($callback instanceof Closure) {
            $state = $callback();

            if (is_array($state)) {
                $this->state()->setState($state);
            }
        }

        return $state;
    }

    public function setCurrentStreamName(string $streamName): void
    {
        $this->currentStreamName = $streamName;
    }

    public function currentStreamName(): ?string
    {
        return $this->currentStreamName;
    }

    public function dispatchSignal(): void
    {
        if ($this->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }
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

    public function clock(): Clock
    {
        return $this->clock;
    }

    public function counter(): ?EventCounter
    {
        return $this->eventCounter;
    }

    private function castEventHandlers(ContextualEventHandler $eventHandler): void
    {
        if ($this->eventHandlers instanceof Closure) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $eventHandler);
        } else {
            foreach ($this->eventHandlers as $eventName => &$handler) {
                $handler = Closure::bind($handler, $eventHandler);
            }
        }
    }

    private function castInitCallback(ContextualEventHandler $eventHandler): array
    {
        if ($this->initCallback instanceof Closure) {
            $callback = Closure::bind($this->initCallback, $eventHandler);

            $result = $callback();

            $this->initCallback = $callback;

            return $result;
        }

        return [];
    }
}
