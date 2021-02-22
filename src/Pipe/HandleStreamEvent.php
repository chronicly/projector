<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageAlias;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\MergeStreamIterator;
use Chronhub\Projector\Factory\StreamEventIterator;
use Chronhub\Projector\Pipe\Middleware\ArrayEventProcessor;
use Chronhub\Projector\Pipe\Middleware\ClosureEventProcessor;
use Illuminate\Support\Collection;

final class HandleStreamEvent implements Pipe
{
    public function __construct(private Chronicler $chronicler,
                                private MessageAlias $messageAlias,
                                private ?ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        // set in context
        $eventHandlers = $context->eventHandlers() instanceof \Closure
            ? new ClosureEventProcessor($context->eventHandlers())
            : new ArrayEventProcessor($context->eventHandlers(), $this->messageAlias);

        $this
            ->retrieveStreams($context)
            ->each(function (Message $message, int $key) use ($context, $eventHandlers): bool {
                return $eventHandlers($context, $message, $key, $this->repository);
            });

        $context->position()->resetRetries();

        return $next($context);
    }

    private function retrieveStreams(ProjectorContext $context): Collection
    {
        $queryFilter = $context->queryFilter();

        $iterator = [];

        // all collection
        foreach ($context->position()->all() as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            $iterator[$streamName] = new StreamEventIterator($events);
        }

        $streamEvents = new MergeStreamIterator(array_keys($iterator), ...array_values($iterator));

        return new Collection($streamEvents);
    }
}
