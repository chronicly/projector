<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Concern;

use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Exception\RuntimeException;
use Closure;

trait HasContextFactory
{
    protected Closure|array|null $initCallback = null;
    protected Closure|array|null $eventHandlers = null;
    protected array $streamsNames = [];
    protected ?ProjectionQueryFilter $queryFilter = null;

    public function initialize(Closure $initCallback): ProjectorContext
    {
        if (null !== $this->initCallback) {
            throw new RuntimeException("Projection already initialized");
        }

        $this->initCallback = $initCallback;

        return $this;
    }

    public function withQueryFilter(ProjectionQueryFilter $queryFilter): ProjectorContext
    {
        if (null !== $this->queryFilter) {
            throw new RuntimeException("Projection query filter already set");
        }

        $this->queryFilter = $queryFilter;

        return $this;
    }

    public function fromStreams(string ...$streamNames): ProjectorContext
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['names'] = $streamNames;

        return $this;
    }

    public function fromCategories(string ...$categories): ProjectorContext
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['categories'] = $categories;

        return $this;
    }

    public function fromAll(): ProjectorContext
    {
        $this->assertStreamsNamesNotSet();

        $this->streamsNames['all'] = true;

        return $this;
    }

    public function when(array $eventHandlers): ProjectorContext
    {
        $this->assertEventHandlersNotSet();

        $this->eventHandlers = $eventHandlers;

        return $this;
    }

    public function whenAny(Closure $eventHandler): ProjectorContext
    {
        $this->assertEventHandlersNotSet();

        $this->eventHandlers = $eventHandler;

        return $this;
    }

    public function keepRunning(): bool
    {
        return $this->keepRunning;
    }

    public function withKeepRunning(bool $keepRunning): void
    {
        $this->keepRunning = $keepRunning;
    }

    public function initCallback(): Closure|array
    {
        return $this->initCallback;
    }

    public function eventHandlers(): Closure|array
    {
        return $this->eventHandlers;
    }

    public function streamsNames(): array
    {
        return $this->streamsNames;
    }

    public function queryFilter(): ProjectionQueryFilter
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
