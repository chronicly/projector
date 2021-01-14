<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRunner;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Exception\RuntimeException;
use Closure;

trait HasContextFactory
{
    protected Closure|null $initCallback = null;
    protected Closure|array|null $eventHandlers = null;
    protected array $streamsNames = [];
    protected ?ProjectionQueryFilter $queryFilter = null;
    protected ProjectorRunner $runner;

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

    public function withRunner(ProjectorRunner $runnerController): void
    {
        $this->runner = $runnerController;
    }

    public function runner(): ProjectorRunner
    {
        return $this->runner;
    }

    public function initCallback(): ?Closure
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
