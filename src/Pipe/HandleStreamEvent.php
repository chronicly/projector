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
use Chronhub\Projector\Factory\ProjectionStatus;
use Chronhub\Projector\Factory\StreamEventIterator;
use Chronhub\Projector\Support\HasGapDetector;
use Generator;

final class HandleStreamEvent implements Pipe
{
    use HasGapDetector;

    private bool $isPersistent;

    public function __construct(private Chronicler $chronicler,
                                private MessageAlias $messageAlias,
                                private ?ProjectorRepository $repository)
    {
        $this->isPersistent = $repository instanceof ProjectorRepository;
    }

    public function __invoke(ProjectorContext $context, callable $next): callable|bool
    {
        $streams = $this->retrieveStreams($context);

        foreach ($streams as $streamName => $events) {
            $context->setCurrentStreamName($streamName);

            $gapDetected = !$this->handleStreamEvents($events, $context);

            if ($this->isPersistent) {
                $this->handleGapDetected($gapDetected);
            }
        }

        return $next($context);
    }

    private function retrieveStreams(ProjectorContext $context): Generator
    {
        $queryFilter = $context->queryFilter();

        foreach ($context->position()->all() as $streamName => $position) {
            $queryFilter->setCurrentPosition($position + 1);

            $events = $this->chronicler->retrieveWithQueryFilter(
                new StreamName($streamName), $queryFilter
            );

            yield from [$streamName => new StreamEventIterator($events)];
        }
    }

    private function handleStreamEvents(StreamEventIterator $streamEvents, ProjectorContext $context): bool
    {
        $eventHandlers = $context->eventHandlers();

        foreach ($streamEvents as $key => $streamEvent) {
            $context->dispatchSignal();

            $currentStreamName = $context->currentStreamName();
            $streamPosition = $context->position()->all()[$currentStreamName];

            if ($this->isPersistent && $this->hasGap($streamPosition, $key)) {
                return false;
            }

            $context->position()->setAt($currentStreamName, $key);

            if ($this->isPersistent) {
                $context->counter()->increment();
            }

            $messageHandler = $eventHandlers;

            if (is_array($eventHandlers)) {
                if (!$messageHandler = $this->determineEventHandler($streamEvent, $eventHandlers)) {
                    if ($this->isPersistent) {
                        $this->persistOnReachedCounter($context);
                    }

                    if ($context->runner()->isStopped()) {
                        break;
                    }

                    continue;
                }
            }

            $projectionState = $messageHandler(
                $streamEvent->eventWithHeaders(), $context->state()->getState()
            );

            if (is_array($projectionState)) {
                $context->state()->setState($projectionState);
            }

            if ($this->isPersistent) {
                $this->persistOnReachedCounter($context);
            }

            if ($context->runner()->isStopped()) {
                break;
            }
        }

        return true;
    }

    private function persistOnReachedCounter(ProjectorContext $context): void
    {
        $persistBlockSize = $context->option()->persistBlockSize();

        if ($context->counter()->equals($persistBlockSize)) {
            $this->repository->persist();

            $context->counter()->reset();

            $context->setStatus($this->repository->loadStatus());

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($context->status(), $keepProjectionRunning)) {
                $context->runner()->stop(true);
            }
        }
    }

    private function determineEventHandler(Message $message, array $eventHandlers): ?callable
    {
        $eventAlias = $this->messageAlias->instanceToAlias($message);

        return $eventHandlers[$eventAlias] ?? null;
    }
}
