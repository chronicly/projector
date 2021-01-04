<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Concern;

use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Query\ProjectionQueryFilter;
use Chronhub\Projector\Projecting\Factory\ContextBuilder;
use Closure;

trait HasProjectorFactory
{
    protected ContextBuilder $builder;

    public function initialize(Closure $initCallback): ProjectorFactory
    {
        $this->builder->initialize($initCallback);

        return $this;
    }

    public function withQueryFilter(ProjectionQueryFilter $queryFilter): ProjectorFactory
    {
        $this->builder->withQueryFilter($queryFilter);

        return $this;
    }

    public function fromStreams(string ...$streamNames): ProjectorFactory
    {
        $this->builder->fromStreams(...$streamNames);

        return $this;
    }

    public function fromCategories(string ...$categories): ProjectorFactory
    {
        $this->builder->fromCategories(...$categories);

        return $this;
    }

    public function fromAll(): ProjectorFactory
    {
        $this->builder->fromAll();

        return $this;
    }

    public function when(array $eventHandlers): ProjectorFactory
    {
        $this->builder->when($eventHandlers);

        return $this;
    }

    public function whenAny(Closure $eventsHandler): ProjectorFactory
    {
        $this->builder->whenAny($eventsHandler);

        return $this;
    }
}
