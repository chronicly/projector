<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Factory;

use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\Pipeline as BasePipeline;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Closure;

final class Pipeline implements BasePipeline
{
    /**
     * @var Pipe[]
     */
    protected array $pipes = [];
    protected ProjectorContext $passable;

    // could be constructed
    public function send(ProjectorContext $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * @param Pipe[] $pipes
     * @return Pipeline
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * @param Closure $destination
     * @return bool
     */
    public function then(Closure $destination): bool
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    private function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                return $pipe($passable, $stack);
            };
        };
    }
}
