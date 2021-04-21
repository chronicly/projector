<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Contracts\Projecting\EventProcessor;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;

abstract class AbstractEventProcessor implements EventProcessor
{
    protected function preProcess(ProjectorContext $context,
                                  Message $message,
                                  int $key,
                                  ?ProjectorRepository $repository): bool
    {
        $context->dispatchSignal();

        $streamName = $context->currentStreamName;

        if ($repository) {
            $timeOfRecording = $message->header(MessageHeader::TIME_OF_RECORDING);

            if ($context->position->hasGap($streamName, $key, $timeOfRecording)) {
                $context->position->setGapDetected(true);

                return false;
            }
        }

        $context->position()->bind($streamName, $key);

        if ($repository) {
            $context->counter()->increment();
        }

        return true;
    }

    protected function afterProcess(ProjectorContext $context, ?array $state, ?ProjectorRepository $repository): bool
    {
        if ($state) {
            $context->state->setState($state);
        }

        if ($repository) {
            $this->persistOnReachedCounter($context, $repository);
        }

        return !$context->runner()->isStopped();
    }

    protected function persistOnReachedCounter(ProjectorContext $context, ProjectorRepository $repository): void
    {
        $persistBlockSize = $context->option->persistBlockSize();

        if ($context->eventCounter->equals($persistBlockSize)) {
            $repository->persist();

            $context->eventCounter->reset();

            $context->status = $repository->loadStatus();

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($context->status, $keepProjectionRunning)) {
                $context->runner()->stop(true);
            }
        }
    }
}
