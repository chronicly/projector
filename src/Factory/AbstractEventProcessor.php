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

        $currentStreamName = $context->currentStreamName();

        if ($repository) {
            $timeOfRecording = $message->header(MessageHeader::TIME_OF_RECORDING);

            if ($context->position()->hasGap($currentStreamName, $key, $timeOfRecording)) {
                $context->position()->sleepWithGapDetected();

                $repository->persist();

                return false;
            }
        }

        $context->position()->setAt($currentStreamName, $key);

        if ($repository) {
            $context->counter()->increment();
        }

        return true;
    }

    protected function afterProcess(ProjectorContext $context, ?array $projectionState, ?ProjectorRepository $repository): bool
    {
        if ($projectionState) {
            $context->state()->setState($projectionState);
        }

        if ($repository) {
            $this->persistOnReachedCounter($context, $repository);
        }

        return !$context->runner()->isStopped();
    }

    protected function persistOnReachedCounter(ProjectorContext $context, ProjectorRepository $repository): void
    {
        $persistBlockSize = $context->option()->persistBlockSize();

        if ($context->counter()->equals($persistBlockSize)) {
            $repository->persist();

            $context->counter()->reset();

            $context->setStatus($repository->loadStatus());

            $keepProjectionRunning = [ProjectionStatus::RUNNING(), ProjectionStatus::IDLE()];

            if (!in_array($context->status(), $keepProjectionRunning)) {
                $context->runner()->stop(true);
            }
        }
    }
}
