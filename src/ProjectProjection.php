<?php
declare(strict_types=1);

namespace Chronhub\Projector;

use Chronhub\Chronicler\Stream\Stream;
use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\DomainEvent;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\ContextualEventHandler;
use Chronhub\Contracts\Projecting\PersistentProjectionProjector;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorFactory;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Foundation\Message\Message;
use Chronhub\Projector\Concern\HasPersistentProjector;
use Chronhub\Projector\Concern\HasProjectorFactory;
use Chronhub\Projector\Context\ContextualProjection;
use JetBrains\PhpStorm\Pure;

final class ProjectProjection implements PersistentProjectionProjector, ProjectorFactory
{
    use HasProjectorFactory, HasPersistentProjector;

    #[Pure]
    public function __construct(protected ProjectorContext $context,
                                protected ProjectorRepository $repository,
                                protected Chronicler $chronicler,
                                protected MessageAlias $messageAlias,
                                protected string $streamName)
    {
    }

    public function emit(DomainEvent $event): void
    {
        $streamName = new StreamName($this->streamName);

        $this->persistIfStreamIsFirstCommit($streamName);

        $this->linkTo($this->streamName, $event);
    }

    public function linkTo(string $streamName, DomainEvent $event): void
    {
        $streamName = new StreamName($streamName);

        $stream = new Stream($streamName, [new Message($event, $event->headers())]);

        $this->determineIfStreamAlreadyExists($streamName)
            ? $this->chronicler->persist($stream)
            : $this->chronicler->persistFirstCommit($stream);
    }

    protected function createContextualEventHandler(): ContextualEventHandler
    {
        $currentStreamName = $this->context->currentStreamName();

        return new ContextualProjection($this, $currentStreamName);
    }

    private function persistIfStreamIsFirstCommit(StreamName $streamName): void
    {
        if (!$this->context->isStreamCreated() && !$this->chronicler->hasStream($streamName)) {
            $this->chronicler->persistFirstCommit(new Stream($streamName));

            $this->context->setStreamCreated();
        }
    }

    private function determineIfStreamAlreadyExists(StreamName $streamName): bool
    {
        if ($this->context->cache()->has($streamName->toString())) {
            $append = true;
        } else {
            $this->context->cache()->push($streamName->toString());
            $append = $this->chronicler->hasStream($streamName);
        }

        return $append;
    }
}
