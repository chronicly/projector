<?php
declare(strict_types=1);

namespace Chronhub\Projector\Pipe;

use Chronhub\Chronicler\Stream\StreamName;
use Chronhub\Contracts\Chronicling\Chronicler;
use Chronhub\Contracts\Projecting\Pipe;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Factory\MergeStreamIterator;
use Chronhub\Projector\Factory\StreamEventIterator;
use function array_keys;
use function array_values;

final class HandleStreamEvent implements Pipe
{
    public function __construct(private Chronicler $chronicler,
                                private ?ProjectorRepository $repository)
    {
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $streams = $this->retrieveStreams($context);

        $eventHandlers = $context->eventHandlers();

        foreach ($streams as $eventStreamKey => $message) {
            $context->setCurrentStreamName($streams->streamName());

            if (!$eventHandlers($context, $message, $eventStreamKey, $this->repository)) {
                return false;
            }
        }

        $context->position()->resetRetries();

        return $next($context);
    }

    private function retrieveStreams(ProjectorContext $context): MergeStreamIterator
    {
        $queryFilter = $context->queryFilter();

        $iterator = [];

        foreach ($context->position()->all() as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            $iterator[$streamName] = new StreamEventIterator($events);
        }

        return new MergeStreamIterator(array_keys($iterator), ...array_values($iterator));
    }
}
