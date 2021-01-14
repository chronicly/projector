<?php
declare(strict_types=1);

namespace Chronhub\Projector\Concern;

use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\RunnerController;
use Chronhub\Projector\PersistentRunner;

trait HasPersistentProjector
{
    protected ProjectorContext $context;
    protected Chronicler $chronicler;
    protected ProjectorRepository $repository;
    protected MessageAlias $messageAlias;
    protected string $streamName;

    public function run(bool $inBackground): void
    {
        $this->context->withRunner(
            new RunnerController($inBackground, false)
        );

        $this->context->bindContextualEventHandler($this->createContextualEventHandler());

        $runner = new PersistentRunner($this, $this->chronicler, $this->messageAlias, $this->repository);

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
