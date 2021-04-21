<?php
declare(strict_types=1);

namespace Chronhub\Projector\Context;

use Chronhub\Contracts\Clock\Clock;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\EventCounter;
use Chronhub\Contracts\Projecting\ProjectionState;
use Chronhub\Contracts\Projecting\ProjectorOption;
use Chronhub\Contracts\Projecting\StreamPosition as Position;
use Chronhub\Projector\Concern\HasContextFactory;
use Chronhub\Projector\Factory\InMemoryState;
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Factory\RunnerController;
use Closure;

class ProjectorContext
{
    use HasContextFactory;

    public ?string $currentStreamName = null;
    public ProjectionState $state;
    public ProjectionStatus $status;

    public function __construct(public ProjectorOption $option,
                                public Position $position,
                                public Clock $clock,
                                public ?EventCounter $eventCounter)
    {
        $this->state = new InMemoryState();
        $this->status = ProjectionStatus::IDLE();
        $this->runner = new RunnerController();
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
                $this->state->setState($state);
            }
        }

        return $state;
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
