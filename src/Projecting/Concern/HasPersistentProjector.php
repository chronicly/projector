<?php
declare(strict_types=1);

namespace Chronhub\Projector\Projecting\Concern;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Projecting\ProjectorRunner;

trait HasPersistentProjector
{
    protected Chronicler $chronicler;
    protected ProjectorRepository $repository;
    protected MessageAlias $messageAlias;

    public function run(bool $keepRunning = true): void
    {
        $this->context->withKeepRunning($keepRunning);

        $this->context->setUp($this->createContextualEventHandler());

        $processor = new ProjectorRunner($this, $this->chronicler, $this->messageAlias, $this->repository);

        $processor->process($this->context);
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
        $callback = $this->repository->delete($withEmittedEvents);

        $callback();
    }

    public function getState(): array
    {
        return $this->context->state()->getState();
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    abstract protected function createContextualEventHandler(): ContextualEventHandler;
}
