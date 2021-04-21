<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Messaging\MessageHeader;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Chronhub\Projector\Context\ProjectorContext;

abstract class AbstractEventProcessor
{
    protected function preProcess(ProjectorContext $context,
                                  Message $message,
                                  int $position,
                                  ?ProjectorRepository $repository): bool
    {
        if ($context->option->dispatchSignal()) {
            pcntl_signal_dispatch();
        }

        $streamName = $context->currentStreamName;

        if ($repository) {
            $timeOfRecording = $message->header(MessageHeader::TIME_OF_RECORDING);

            if ($context->position->detectGap($streamName, $position, $timeOfRecording)) {
                return false;
            }
        }

        $context->position->bind($streamName, $position);

        if ($repository) {
            $context->eventCounter->increment();
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
