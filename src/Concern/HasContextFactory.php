<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\ProjectorTimer;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Factory\ArrayEventProcessor;
use Chronhub\Projector\Factory\ClosureEventProcessor;
use Chronhub\Projector\Factory\RunnerController;
use Chronhub\Projector\Support\Timer\NullTimer;
use Chronhub\Projector\Support\Timer\ProcessTimer;
use Closure;
use function count;

trait HasContextFactory
{
    protected Closure|null $initCallback = null;
    protected Closure|array|null $eventHandlers = null;
    protected array $streamsNames = [];
    protected ?ProjectionQueryFilter $queryFilter = null;
    protected null|ProjectorTimer $timer = null;
    protected RunnerController $runner;

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

    public function withTimer(int|string $timer): ProjectorContext
    {
        if (null !== $this->timer) {
            throw new RuntimeException("Projection timer already set");
        }

        $this->timer = new ProcessTimer($this->clock, $timer);

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

    public function runner(): RunnerController
    {
        return $this->runner;
    }

    public function eventHandlers(): callable
    {
        if ($this->eventHandlers instanceof Closure) {
            return new ClosureEventProcessor($this->eventHandlers);
        }

        return new ArrayEventProcessor($this->eventHandlers);
    }

    public function streamsNames(): array
    {
        return $this->streamsNames;
    }

    public function queryFilter(): ProjectionQueryFilter
    {
        return $this->queryFilter;
    }

    public function timer(): ProjectorTimer
    {
        return $this->timer ?? new NullTimer();
    }

    protected function validate(): void
    {
        if (count($this->streamsNames) === 0) {
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
        if (count($this->streamsNames) > 0) {
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
