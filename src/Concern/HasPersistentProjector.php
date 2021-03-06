<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;
use Chronhub\Projector\PersistentRunner;

trait HasPersistentProjector
{
    protected ProjectorContext $context;
    protected Chronicler $chronicler;
    protected ProjectorRepository $repository;
    protected string $streamName;

    public function run(bool $inBackground): void
    {
        $this->context->runner()->runInBackground($inBackground);

        $this->context->cast($this->createContextualProjector());

        $runner = new PersistentRunner($this, $this->chronicler, $this->repository);

        $runner($this->context);
    }

    public function stop(): void
    {
        $this->repository->stop();
    }

    public function reset(): void
    {
        $this->repository->reset();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);
    }

    public function getState(): array
    {
        return $this->context->state->getState();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    abstract protected function createContextualProjector(): ContextualEventHandler;
}
