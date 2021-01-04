<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Factory;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Closure;

// todo assert
final class ContextBuilder
{
    public Closure|array $initCallback;
    public Closure|array $eventHandlers;
    public array $streamsNames;
    public ProjectionQueryFilter $queryFilter;

    public function bindEventHandlers(ContextualEventHandler $eventHandler): void
    {
        if ($this->eventHandlers instanceof Closure) {
            $this->eventHandlers = Closure::bind($this->eventHandlers, $eventHandler);
        } else {
            foreach ($this->eventHandlers as $eventName => &$handler) {
                $handler = Closure::bind($handler, $eventHandler);
            }
        }
    }

    public function bindInitCallback(ContextualEventHandler $eventHandler): array
    {
        if ($this->initCallback instanceof Closure) {
            $callback = Closure::bind($this->initCallback, $eventHandler);

            $result = $callback();

            $this->initCallback = $result;

            return $result;
        }

        return [];
    }

    public function initialize(Closure $initCallback): self
    {
        $this->initCallback = $initCallback;

        return $this;
    }

    public function withQueryFilter(ProjectionQueryFilter $queryFilter): self
    {
        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function fromStreams(string ...$streamNames): self
    {
        $this->streamsNames['names'] = $streamNames;

        return $this;
    }

    public function fromCategories(string ...$categories): self
    {
        $this->streamsNames['categories'] = $categories;

        return $this;
    }

    public function fromAll(): self
    {
        $this->streamsNames['all'] = true;

        return $this;
    }

    public function when(array $eventHandlers): self
    {
        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function whenAny(Closure $eventHandler): self
    {
        $this->eventHandlers = $eventHandler;

        return $this;
    }

    public function getInitCallback(): Closure|array
    {
        return $this->initCallback;
    }

    public function getEventHandlers(): Closure|array
    {
        return $this->eventHandlers;
    }

    public function getStreamsNames(): array
    {
        return $this->streamsNames;
    }

    public function getQueryFilter(): ?ProjectionQueryFilter
    {
        return $this->queryFilter;
    }
}
