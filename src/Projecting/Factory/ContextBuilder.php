<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Factory;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Exception\RuntimeException;
use Closure;

final class ContextBuilder
{
    public Closure|array|null $initCallback = null;
    public Closure|array|null $eventHandlers = null;
    public array $streamsNames = [];
    public ?ProjectionQueryFilter $queryFilter = null;

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
        if (null !== $this->initCallback) {
            throw new RuntimeException("Projection already initialized");
        }

        $this->initCallback = $initCallback;

        return $this;
    }

    public function withQueryFilter(ProjectionQueryFilter $queryFilter): self
    {
        if (null !== $this->queryFilter) {
            throw new RuntimeException("Projection query filter already set");
        }

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function fromStreams(string ...$streamNames): self
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['names'] = $streamNames;

        return $this;
    }

    public function fromCategories(string ...$categories): self
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['categories'] = $categories;

        return $this;
    }

    public function fromAll(): self
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['all'] = true;

        return $this;
    }

    public function when(array $eventHandlers): self
    {
        $this->assertEventHandlersNotSet();

        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function whenAny(Closure $eventHandler): self
    {
        $this->assertEventHandlersNotSet();

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

    public function getQueryFilter(): ProjectionQueryFilter
    {
        return $this->queryFilter;
    }

    public function validate(): void
    {
        if (empty($this->streamsNames)) {
            throw new RuntimeException("Projection streams all|names|categories not set");
        }

        if (null === $this->eventHandlers) {
            throw new RuntimeException("Projection event handlers not set");
        }

        if (null === $this->queryFilter) {
            throw new RuntimeException("Projection query filter not set");
        }
    }

    private function assertStreamsNamesNotSet(): void
    {
        if (!empty($this->streamsNames)) {
            throw new RuntimeException("Projection streams all|names|categories already set");
        }
    }

    private function assertEventHandlersNotSet(): void
    {
        if (null !== $this->eventHandlers) {
            throw new RuntimeException("Projection event handlers already set");
        }
    }
}
