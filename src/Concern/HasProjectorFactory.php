<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Context\ProjectorContext;
use Closure;

trait HasProjectorFactory
{
    protected ProjectorContext $context;

    public function initialize(Closure $initCallback): ProjectorFactory
    {
        $this->context->initialize($initCallback);

        return $this;
    }

    public function withQueryFilter(ProjectionQueryFilter $queryFilter): ProjectorFactory
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(int|string $time): ProjectorFactory
    {
        $this->context->withTimer($time);

        return $this;
    }

    public function fromStreams(string ...$streamNames): ProjectorFactory
    {
        $this->context->fromStreams(...$streamNames);

        return $this;
    }

    public function fromCategories(string ...$categories): ProjectorFactory
    {
        $this->context->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): ProjectorFactory
    {
        $this->context->fromAll();

        return $this;
    }

    public function when(array $eventHandlers): ProjectorFactory
    {
        $this->context->when($eventHandlers);

        return $this;
    }

    public function whenAny(Closure $eventsHandler): ProjectorFactory
    {
        $this->context->whenAny($eventsHandler);

        return $this;
    }
}
