<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\Exception\ProjectionAlreadyRunning;
use Closure;
use Throwable;

final class Pipeline
{
    private array $pipes = [];
    private ProjectorContext $passable;

    public function __construct(private ?ProjectorRepository $repository)
    {
    }

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
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        try {
            return fn($passable) => $destination($passable);
        } catch (Throwable $exception) {
            $this->releaseLockOnException($exception);

            throw $exception;
        }
    }

    private function carry(): Closure
    {
        try {
            return fn($stack, $pipe) => fn($passable) => $pipe($passable, $stack);
        } catch (Throwable $exception) {
            $this->releaseLockOnException($exception);

            throw $exception;
        }
    }

    private function releaseLockOnException(Throwable $exception): void
    {
        if ($this->repository && !$exception instanceof ProjectionAlreadyRunning) {
            $this->repository->releaseLock();
        }
    }
}
