<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

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

    public function send(ProjectorContext $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(Closure $destination): bool
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return fn($passable) => $destination($passable);
    }

    private function carry(): Closure
    {
        return fn($stack, $pipe) => fn($passable) => $pipe($passable, $stack);
    }
}
