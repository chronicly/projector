<?php
declare(strict_types=1);

namespace Chronhub\Projector\Factory;

use Chronhub\Contracts\Messaging\Message;
use Chronhub\Contracts\Projecting\ProjectorContext;
use Chronhub\Contracts\Projecting\ProjectorRepository;
use Closure;

final class ClosureEventProcessor extends AbstractEventProcessor
{
    public function __construct(private Closure $eventHandlers)
    {
    }

    public function __invoke(ProjectorContext $context, Message $message, int $key, ?ProjectorRepository $repository): bool
    {
        if (!$this->preProcess($context, $message, $key, $repository)) {
            return false;
        }

        $state = ($this->eventHandlers)($message->eventWithHeaders(), $context->state()->getState());

        return $this->afterProcess($context, $state, $repository);
    }
}
